<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Sale;
use App\Models\Feedback;
use App\Models\Plan;
use Illuminate\Http\Request;

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
            'user_count'     => User::count(),
            'sale_today'     => Sale::whereDate('created_at', today())->count(),
            'sale_total'     => Sale::sum('total_amount'),
            'feedback_open'  => Feedback::where('status', 'open')->count(),
            'feedback_total' => Feedback::count(),
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
        $query = Feedback::with('tenant')->orderByDesc('created_at');

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
        $query = User::with(['tenant', 'role'])
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
