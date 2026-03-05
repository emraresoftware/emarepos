<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::withCount(['users', 'restaurantTables', 'cashRegisters'])
            ->orderBy('name')
            ->get();

        return view('pos.branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
        ]);

        $data['tenant_id'] = session('tenant_id');
        $data['is_active'] = true;
        $branch = Branch::create($data);

        return response()->json(['success' => true, 'branch' => $branch]);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $branch->update($data);
        return response()->json(['success' => true, 'branch' => $branch->fresh()]);
    }
}
