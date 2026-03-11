<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\FirmGroup;
use App\Models\FirmPhone;
use App\Models\AccountTransaction;
use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\ActivityLog;

class FirmController extends Controller
{
    public function index(Request $request)
    {
        $query = Firm::where('is_active', true)->with(['group', 'phones'])->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('tax_number', 'like', "%{$s}%");
            });
        }

        if ($request->filled('group_id')) {
            $query->where('firm_group_id', $request->group_id);
        }

        $firms = $query->paginate(50)->withQueryString();

        // 3 sorgu yerine tek aggregate
        $statsAgg = Firm::where('is_active', true)
            ->selectRaw("COUNT(*) as total_firms, COALESCE(SUM(CASE WHEN balance < 0 THEN balance ELSE 0 END), 0) as total_debt, COALESCE(SUM(CASE WHEN balance > 0 THEN balance ELSE 0 END), 0) as total_credit")
            ->first();
        $stats = [
            'total_firms'  => (int) ($statsAgg->total_firms ?? 0),
            'total_debt'   => (float) ($statsAgg->total_debt ?? 0),
            'total_credit' => (float) ($statsAgg->total_credit ?? 0),
        ];

        $groups = FirmGroup::where('is_active', true)->withCount('firms')->orderBy('name')->get();

        return view('pos.firms.index', compact('firms', 'stats', 'groups'));
    }

    public function show(Firm $firm)
    {
        if ($firm->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $firm->load('phones');

        $transactions = AccountTransaction::where('firm_id', $firm->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $payments = AccountTransaction::where('firm_id', $firm->id)
            ->where('type', 'payment')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $purchaseInvoices = PurchaseInvoice::where('firm_id', $firm->id)
            ->orderByDesc('invoice_date')
            ->limit(50)
            ->get();

        $totalPurchase = $purchaseInvoices->sum('grand_total');
        $totalPayment  = $payments->sum('amount');

        return response()->json([
            'firm'             => $firm,
            'phones'           => $firm->phones,
            'transactions'     => $transactions,
            'payments'         => $payments,
            'purchase_invoices' => $purchaseInvoices,
            'total_purchase'   => $totalPurchase,
            'total_payment'    => $totalPayment,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'firm_group_id' => ['nullable', 'integer', Rule::exists('firm_groups', 'id')->where('tenant_id', session('tenant_id'))],

            'tax_number' => 'nullable|string|max:50',
            'tax_office' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $data['tenant_id'] = session('tenant_id');
        $data['is_active'] = true;
        $firm = Firm::create($data);

        // Çoklu telefon
        if ($request->has('phones')) {
            foreach ($request->phones as $p) {
                if (!empty($p['phone'])) {
                    FirmPhone::create([
                        'firm_id'    => $firm->id,
                        'phone'      => $p['phone'],
                        'type'       => $p['type'] ?? 'mobile',
                        'is_primary' => !empty($p['is_primary']),
                    ]);
                }
            }
            $primary = collect($request->phones)->firstWhere('is_primary', true)
                ?? collect($request->phones)->first();
            if ($primary && !empty($primary['phone'])) {
                $firm->update(['phone' => $primary['phone']]);
            }
        }

        return response()->json(['success' => true, 'firm' => $firm->load(['group', 'phones'])]);
    }

    public function update(Request $request, Firm $firm)
    {
        if ($firm->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'firm_group_id' => ['nullable', 'integer', Rule::exists('firm_groups', 'id')->where('tenant_id', session('tenant_id'))],

            'tax_number' => 'nullable|string|max:50',
            'tax_office' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $firm->update($data);

        // Çoklu telefon güncelle
        if ($request->has('phones')) {
            $firm->phones()->delete();
            foreach ($request->phones as $p) {
                if (!empty($p['phone'])) {
                    FirmPhone::create([
                        'firm_id'    => $firm->id,
                        'phone'      => $p['phone'],
                        'type'       => $p['type'] ?? 'mobile',
                        'is_primary' => !empty($p['is_primary']),
                    ]);
                }
            }
            $primary = collect($request->phones)->firstWhere('is_primary', true)
                ?? collect($request->phones)->first();
            if ($primary && !empty($primary['phone'])) {
                $firm->update(['phone' => $primary['phone']]);
            }
        }

        return response()->json(['success' => true, 'firm' => $firm->fresh()->load(['group', 'phones'])]);
    }

    public function destroy(Firm $firm)
    {
        if ($firm->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        if (abs($firm->balance) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Bakiyesi olan cari silinemez. Önce bakiyeyi sıfırlayın.',
            ], 422);
        }

        $firm->update(['is_active' => false]);
        ActivityLog::log('delete', 'Firma pasife alındı: ' . $firm->name, $firm);
        return response()->json(['success' => true, 'message' => 'Cari pasife alındı.']);
    }

    public function addPayment(Request $request, Firm $firm)
    {
        if ($firm->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($firm, $request) {
            $firm = Firm::where('id', $firm->id)->lockForUpdate()->first();
            $firm->increment('balance', $request->amount);
            $firm->refresh();

            AccountTransaction::create([
                'tenant_id' => session('tenant_id'),
                'firm_id' => $firm->id,
                'type' => 'payment',
                'amount' => $request->amount,
                'balance_after' => $firm->balance,
                'description' => $request->description ?: ('Ödeme: ' . $firm->name),
                'transaction_date' => \Carbon\Carbon::now(),
            ]);

            return response()->json(['success' => true, 'firm' => $firm]);
        });
    }

    public function addDebt(Request $request, Firm $firm)
    {
        if ($firm->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($firm, $request) {
            $firm = Firm::where('id', $firm->id)->lockForUpdate()->first();
            $firm->decrement('balance', $request->amount);
            $firm->refresh();

            AccountTransaction::create([
                'tenant_id'        => session('tenant_id'),
                'firm_id'          => $firm->id,
                'type'             => 'debt',
                'amount'           => -$request->amount,
                'balance_after'    => $firm->balance,
                'description'      => $request->description ?? 'Borç Eklendi',
                'transaction_date' => Carbon::now(),
            ]);

            return response()->json(['success' => true, 'firm' => $firm]);
        });
    }

    // ─── Cari Grupları ────────────────────────────────────────

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $data['tenant_id'] = session('tenant_id');
        $group = FirmGroup::create($data);
        return response()->json(['success' => true, 'group' => $group]);
    }

    public function updateGroup(Request $request, FirmGroup $group)
    {
        if ($group->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $group->update($data);
        return response()->json(['success' => true, 'group' => $group->fresh()]);
    }

    public function destroyGroup(FirmGroup $group)
    {
        if ($group->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        if ($group->firms()->exists()) {
            // Grubu silmeden önce carilerin group_id'sini null yap
            $group->firms()->update(['firm_group_id' => null]);
        }
        $group->delete();
        return response()->json(['success' => true]);
    }
}
