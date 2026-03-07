<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $query = Staff::orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('role', 'like', "%{$s}%");
            });
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->active === '1');
        }

        $staff = $query->paginate(30)->withQueryString();

        $stats = [
            'total'        => Staff::count(),
            'active'       => Staff::where('is_active', true)->count(),
            'total_sales'  => Staff::sum('total_sales'),
            'top_seller'   => Staff::orderBy('total_sales', 'desc')->first()?->name ?? '—',
        ];

        return view('pos.staff.index', compact('staff', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'role'  => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'pin'   => 'nullable|string|max:10',
        ]);
        $data['tenant_id'] = session('tenant_id');
        $data['branch_id'] = session('branch_id');
        $data['is_active'] = true;
        $data['permissions'] = $request->input('permissions', []);

        $member = Staff::create($data);
        return response()->json(['success' => true, 'staff' => $member]);
    }

    public function update(Request $request, Staff $staff)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'role'      => 'nullable|string|max:100',
            'phone'     => 'nullable|string|max:30',
            'email'     => 'nullable|email|max:255',
            'is_active' => 'nullable|boolean',
            'pin'       => 'nullable|string|max:10',
        ]);
        $data['permissions'] = $request->input('permissions', []);
        $staff->update($data);
        return response()->json(['success' => true]);
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();
        return response()->json(['success' => true]);
    }
}
