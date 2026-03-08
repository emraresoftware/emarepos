<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Sale;
use App\Models\Staff;
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

    public function destroy(Branch $branch)
    {
        // Aktif şubeyi silmeye çalışıyorsa
        if ((int) session('branch_id') === $branch->id) {
            return response()->json(['success' => false, 'message' => 'Aktif şubenizi silemezsiniz.'], 422);
        }

        // Bağlı satış var mı?
        $saleCount = Sale::where('branch_id', $branch->id)->count();
        if ($saleCount > 0) {
            // Soft-delete: pasife çek
            $branch->update(['is_active' => false]);
            return response()->json(['success' => true, 'message' => "Şube pasife alındı ({$saleCount} satış kaydı var, kalıcı silme yapılmadı)."]);
        }

        // Bağlı personel var mı?
        $staffCount = Staff::where('branch_id', $branch->id)->count();
        if ($staffCount > 0) {
            $branch->update(['is_active' => false]);
            return response()->json(['success' => true, 'message' => "Şube pasife alındı ({$staffCount} personel kaydı var)."]);
        }

        $branch->delete();
        return response()->json(['success' => true, 'message' => 'Şube silindi.']);
    }
}
