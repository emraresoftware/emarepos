<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\AccountTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\ActivityLog;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::where('is_active', true)
            ->with('group')
            ->withSum('sales', 'grand_total')
            ->withMax('sales', 'sold_at')
            ->orderBy('name');
        
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('group_id')) {
            $query->where('customer_group_id', $request->group_id);
        }
        
        $customers = $query->paginate(50)->withQueryString();
        $groups = CustomerGroup::where('is_active', true)->withCount('customers')->orderBy('name')->get();
        return view('pos.customers.index', compact('customers', 'groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'customer_group_id' => ['nullable', 'integer', Rule::exists('customer_groups', 'id')->where('tenant_id', session('tenant_id'))],
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'type' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'tax_office' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'district' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        
        $data['tenant_id'] = session('tenant_id');
        $customer = Customer::create($data);
        
        return response()->json(['success' => true, 'customer' => $customer]);
    }

    public function show(Customer $customer)
    {
        if ($customer->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        $transactions = AccountTransaction::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        
        $sales = $customer->sales()->orderBy('sold_at', 'desc')->limit(20)->get();
        
        return response()->json([
            'customer' => $customer,
            'transactions' => $transactions,
            'recent_sales' => $sales,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        if ($customer->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'customer_group_id' => ['nullable', 'integer', Rule::exists('customer_groups', 'id')->where('tenant_id', session('tenant_id'))],
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'type' => 'nullable|string',
            'tax_number' => 'nullable|string',
            'tax_office' => 'nullable|string',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'district' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $customer->update($data);

        return response()->json(['success' => true, 'customer' => $customer->fresh()]);
    }

    public function addPayment(Request $request, Customer $customer)
    {
        if ($customer->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($customer, $request) {
            $customer = Customer::where('id', $customer->id)->lockForUpdate()->first();
            $customer->increment('balance', $request->amount);
            $customer->refresh();

            AccountTransaction::create([
                'tenant_id' => session('tenant_id'),
                'customer_id' => $customer->id,
                'type' => 'payment',
                'amount' => $request->amount,
                'balance_after' => $customer->balance,
                'description' => $request->description ?? 'Tahsilat',
                'transaction_date' => \Carbon\Carbon::now(),
            ]);

            return response()->json(['success' => true, 'customer' => $customer->fresh()]);
        });
    }

    public function destroy(Customer $customer)
    {
        if ($customer->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        if (abs($customer->balance) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Bakiyesi olan müşteri silinemez. Önce bakiyeyi sıfırlayın.',
            ], 422);
        }

        $customer->update(['is_active' => false]);
        ActivityLog::log('delete', 'Müşteri pasife alındı: ' . $customer->name, $customer);
        return response()->json(['success' => true, 'message' => 'Müşteri pasife alındı.']);
    }

    // ─── Müşteri Grupları ─────────────────────────────────────

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $data['tenant_id'] = session('tenant_id');
        $group = CustomerGroup::create($data);
        return response()->json(['success' => true, 'group' => $group]);
    }

    public function updateGroup(Request $request, CustomerGroup $group)
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

    public function destroyGroup(CustomerGroup $group)
    {
        if ($group->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        if ($group->customers()->exists()) {
            $group->customers()->update(['customer_group_id' => null]);
        }
        $group->delete();
        return response()->json(['success' => true]);
    }
}
