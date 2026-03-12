<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomerPhone;
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
            ->with(['group', 'phones'])
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
            'credit_limit'      => 'nullable|numeric|min:0',
        ]);
        
        $data['tenant_id'] = session('tenant_id');
        $customer = Customer::create($data);

        // Çoklu telefon numaraları
        if ($request->has('phones')) {
            foreach ($request->phones as $p) {
                if (!empty($p['phone'])) {
                    CustomerPhone::create([
                        'customer_id' => $customer->id,
                        'phone'       => $p['phone'],
                        'type'        => $p['type'] ?? 'mobile',
                        'is_primary'  => !empty($p['is_primary']),
                    ]);
                }
            }
            $primary = collect($request->phones)->firstWhere('is_primary', true)
                ?? collect($request->phones)->first();
            if ($primary && !empty($primary['phone'])) {
                $customer->update(['phone' => $primary['phone']]);
            }
        }

        return response()->json(['success' => true, 'customer' => $customer->load('phones')]);
    }

    public function show(Customer $customer)
    {
        if ($customer->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        $customer->load('phones');

        $transactions = AccountTransaction::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            
            ->get();

        $sales = $customer->sales()
            ->with('items')
            ->orderBy('sold_at', 'desc')
            
            ->get();

        return response()->json([
            'customer'     => $customer,
            'phones'       => $customer->phones,
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
            'credit_limit'      => 'nullable|numeric|min:0',
        ]);

        $customer->update($data);

        // Çoklu telefon numaraları güncelle
        if ($request->has('phones')) {
            $customer->phones()->delete();
            foreach ($request->phones as $p) {
                if (!empty($p['phone'])) {
                    CustomerPhone::create([
                        'customer_id' => $customer->id,
                        'phone'       => $p['phone'],
                        'type'        => $p['type'] ?? 'mobile',
                        'is_primary'  => !empty($p['is_primary']),
                    ]);
                }
            }
            $primary = collect($request->phones)->firstWhere('is_primary', true)
                ?? collect($request->phones)->first();
            if ($primary && !empty($primary['phone'])) {
                $customer->update(['phone' => $primary['phone']]);
            }
        }

        return response()->json(['success' => true, 'customer' => $customer->fresh()->load('phones')]);
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

    public function addDebt(Request $request, Customer $customer)
    {
        if ($customer->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }
        $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($customer, $request) {
            $customer = Customer::where('id', $customer->id)->lockForUpdate()->first();
            $customer->decrement('balance', $request->amount);
            $customer->refresh();

            AccountTransaction::create([
                'tenant_id'        => session('tenant_id'),
                'customer_id'      => $customer->id,
                'type'             => 'debt',
                'amount'           => -$request->amount,
                'balance_after'    => $customer->balance,
                'description'      => $request->description ?? 'Borç Eklendi',
                'transaction_date' => Carbon::now(),
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
