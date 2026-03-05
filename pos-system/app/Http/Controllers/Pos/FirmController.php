<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Firm;
use App\Models\AccountTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FirmController extends Controller
{
    public function index(Request $request)
    {
        $query = Firm::where('is_active', true)->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('tax_number', 'like', "%{$s}%");
            });
        }

        $firms = $query->paginate(50);

        $stats = [
            'total_firms' => Firm::where('is_active', true)->count(),
            'total_debt' => Firm::where('is_active', true)->where('balance', '<', 0)->sum('balance'),
            'total_credit' => Firm::where('is_active', true)->where('balance', '>', 0)->sum('balance'),
        ];

        return view('pos.firms.index', compact('firms', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
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

        return response()->json(['success' => true, 'firm' => $firm]);
    }

    public function update(Request $request, Firm $firm)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'tax_number' => 'nullable|string|max:50',
            'tax_office' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $firm->update($data);
        return response()->json(['success' => true, 'firm' => $firm->fresh()]);
    }

    public function addPayment(Request $request, Firm $firm)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);

        $firm->increment('balance', $request->amount);

        return response()->json(['success' => true, 'firm' => $firm->fresh()]);
    }
}
