<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Sale;
use App\Models\Feedback;
use App\Models\Plan;
use App\Models\Branch;
use App\Models\Role;
use App\Models\PaymentType;
use App\Models\TaxRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Süper Admin Paneli — Ana Sayfa
     * GET /admin
     */
    public function dashboard()
    {
        $stats = [
            'tenant_count'   => Tenant::count(),
            'user_count'     => User::withoutGlobalScope('tenant')->count(),
            'sale_today'     => Sale::withoutGlobalScope('tenant')->whereDate('sold_at', today())->count(),
            'sale_total'     => Sale::withoutGlobalScope('tenant')->sum('grand_total'),
            'feedback_open'  => Feedback::withoutGlobalScope('tenant')->where('status', 'open')->count(),
            'feedback_total' => Feedback::withoutGlobalScope('tenant')->count(),
        ];

        // Plan dağılımı
        $planDagilim = Plan::withCount('tenants')->get();

        // Son 7 günlük kayıt (tenant)
        $sonTenantlar = Tenant::with('plan')
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        return view('admin.dashboard', compact('stats', 'planDagilim', 'sonTenantlar'));
    }

    /**
     * Tenant Listesi
     * GET /admin/tenants
     */
    public function tenants(Request $request)
    {
        $query = Tenant::with(['plan'])
            ->withCount('users')
            ->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('billing_email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $tenants = $query->paginate(20)->withQueryString();
        $planlar  = Plan::orderBy('sort_order')->get();

        return view('admin.tenants.index', compact('tenants', 'planlar'));
    }

    /**
     * Yeni Tenant oluştur (tam kurulum: şube + vergiler + ödeme tipleri + admin kullanıcı)
     * POST /admin/tenants
     */
    public function tenantStore(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'slug'           => 'required|string|max:100|unique:tenants,slug|regex:/^[a-z0-9\-]+$/',
            'billing_email'  => 'required|email|max:255',
            'plan_id'        => 'required|exists:plans,id',
            'status'         => 'required|in:active,trial,suspended',
            'admin_name'     => 'required|string|max:255',
            'admin_email'    => 'required|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:6',
            'branch_name'    => 'nullable|string|max:255',
            'branch_city'    => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($data) {
            // 1. Tenant
            $tenant = Tenant::create([
                'name'          => $data['name'],
                'slug'          => $data['slug'],
                'status'        => $data['status'],
                'plan_id'       => $data['plan_id'],
                'billing_email' => $data['billing_email'],
                'trial_ends_at' => $data['status'] === 'trial' ? now()->addDays(14) : null,
            ]);

            // 2. Şube
            $branch = Branch::create([
                'tenant_id' => $tenant->id,
                'name'      => $data['branch_name'] ?: 'Merkez Şube',
                'code'      => 'MERKEZ',
                'city'      => $data['branch_city'] ?: 'İstanbul',
                'is_active' => true,
                'settings'  => ['currency' => 'TRY', 'timezone' => 'Europe/Istanbul'],
            ]);

            // 3. Vergi oranları
            foreach ([
                ['KDV %1',  'kdv_1',  1.0,  false, 1, 'Temel gıda'],
                ['KDV %10', 'kdv_10', 10.0, true,  2, 'Gıda / restoran'],
                ['KDV %20', 'kdv_20', 20.0, false, 3, 'Genel KDV'],
            ] as [$taxName, $taxCode, $taxRate, $isDefault, $sort, $desc]) {
                TaxRate::create([
                    'tenant_id'   => $tenant->id,
                    'name'        => $taxName,
                    'code'        => $taxCode,
                    'rate'        => $taxRate,
                    'type'        => 'percentage',
                    'description' => $desc,
                    'is_default'  => $isDefault,
                    'is_active'   => true,
                    'sort_order'  => $sort,
                ]);
            }

            // 4. Ödeme tipleri
            foreach ([
                ['Nakit', 'cash', 1], ['Kredi Kartı', 'card', 2],
                ['Veresiye', 'credit', 3], ['Havale / EFT', 'transfer', 4],
            ] as [$ptName, $ptCode, $ptSort]) {
                PaymentType::create([
                    'tenant_id' => $tenant->id,
                    'name'      => $ptName,
                    'code'      => $ptCode,
                    'is_active' => true,
                    'sort_order' => $ptSort,
                ]);
            }

            // 5. Admin rolünü bul
            $adminRole = Role::where('code', 'admin')->first();

            // 6. Admin kullanıcı
            User::create([
                'tenant_id'      => $tenant->id,
                'branch_id'      => $branch->id,
                'role_id'        => $adminRole?->id,
                'is_super_admin' => false,
                'name'           => $data['admin_name'],
                'email'          => $data['admin_email'],
                'password'       => Hash::make($data['admin_password']),
            ]);
        });

        return back()->with('success', "'{$data['name']}' işletmesi başarıyla oluşturuldu.");
    }

    /**
     * Tenant sil
     * DELETE /admin/tenants/{tenant}
     */
    public function tenantDestroy(Tenant $tenant)
    {
        $name = $tenant->name;
        $tenant->delete();
        return back()->with('success', "'{$name}' silindi.");
    }

    /**
     * Tenant durum değiştir (active/suspended)
     * PATCH /admin/tenants/{tenant}/status
     */
    public function tenantStatus(Request $request, Tenant $tenant)
    {
        $request->validate(['status' => 'required|in:active,trial,suspended,cancelled']);
        $tenant->update(['status' => $request->status]);

        return back()->with('success', "'{$tenant->name}' durumu güncellendi.");
    }

    /**
     * Feedback yönetim sayfası — admin versiyonu (tüm tenantlar)
     * GET /admin/feedbacks
     */
    public function feedbacks(Request $request)
    {
        $query = Feedback::withoutGlobalScope('tenant')->orderByDesc('created_at');

        if ($s = $request->input('status')) {
            $query->where('status', $s);
        }
        if ($c = $request->input('category')) {
            $query->where('category', $c);
        }

        $feedbacks = $query->paginate(25)->withQueryString();

        return view('admin.feedbacks.index', compact('feedbacks'));
    }

    /**
     * Kullanıcı listesi — tüm tenantlar
     * GET /admin/users
     */
    public function users(Request $request)
    {
        $query = User::withoutGlobalScope('tenant')
            ->with(['tenant', 'role'])
            ->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(25)->withQueryString();

        return view('admin.users.index', compact('users'));
    }
}
