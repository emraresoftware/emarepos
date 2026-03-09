<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\FirmGroup;
use App\Models\AccountTransaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\ActivityLog;

class FirmController extends Controller
{
    public function index(Request $request)
    {
        $query = Firm::where('is_active', true)->with('group')->orderBy('name');

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

        $stats = [
            'total_firms' => Firm::where('is_active', true)->count(),
            'total_debt' => Firm::where('is_active', true)->where('balance', '<', 0)->sum('balance'),
            'total_credit' => Firm::where('is_active', true)->where('balance', '>', 0)->sum('balance'),
        ];

        $groups = FirmGroup::where('is_active', true)->withCount('firms')->orderBy('name')->get();

        return view('pos.firms.index', compact('firms', 'stats', 'groups'));
    }

    public function show(Firm $firm)
    {
        $transactions = AccountTransaction::where('firm_id', $firm->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'firm' => $firm,
            'transactions' => $transactions,
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

        return response()->json(['success' => true, 'firm' => $firm->load('group')]);
    }

    public function update(Request $request, Firm $firm)
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

        $firm->update($data);
        return response()->json(['success' => true, 'firm' => $firm->fresh()->load('group')]);
    }

    public function destroy(Firm $firm)
    {
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
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $group->update($data);
        return response()->json(['success' => true, 'group' => $group->fresh()]);
    }

    public function destroyGroup(FirmGroup $group)
    {
        if ($group->firms()->exists()) {
            // Grubu silmeden önce carilerin group_id'sini null yap
            $group->firms()->update(['firm_group_id' => null]);
        }
        $group->delete();
        return response()->json(['success' => true]);
    }
}
