<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['role', 'branch', 'additionalRoles'])->orderBy('name');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->paginate(50)->withQueryString();
        $roles = Role::orderBy('name')->get();
        $branchRoles = Role::whereIn('scope', ['branch', 'both'])->orderBy('name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('pos.users.index', compact('users', 'roles', 'branchRoles', 'branches'));
    }

    public function store(Request $request)
    {
        $tenantId = session('tenant_id');
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', Rule::unique('users', 'email')->where('tenant_id', $tenantId)],
            'password'  => 'required|string|min:6',
            'role_id'   => ['nullable', 'integer', Rule::exists('roles', 'id')->where('tenant_id', $tenantId)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('tenant_id', $tenantId)],
            'branch_role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where('tenant_id', $tenantId)],
        ]);

        if (!empty($data['branch_role_id']) && empty($data['branch_id'])) {
            return response()->json(['success' => false, 'message' => 'Şube rolü için şube seçilmelidir.'], 422);
        }

        $data['tenant_id'] = session('tenant_id');
        $data['password'] = Hash::make($data['password']);
        $branchRoleId = $data['branch_role_id'] ?? null;
        unset($data['branch_role_id']);
        $user = User::create($data);

        if (!empty($branchRoleId) && !empty($data['branch_id'])) {
            $user->additionalRoles()->attach($branchRoleId, [
                'tenant_id' => $tenantId,
                'branch_id' => $data['branch_id'],
                'created_at' => now(),
            ]);
        }

        return response()->json(['success' => true, 'user' => $user->load(['role', 'branch'])]);
    }

    public function update(Request $request, User $user)
    {
        $tenantId = session('tenant_id');

        if ($user->tenant_id !== (int) $tenantId) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', Rule::unique('users', 'email')->where('tenant_id', $tenantId)->ignore($user->id)],
            'password'  => 'nullable|string|min:6',
            'role_id'   => ['nullable', 'integer', Rule::exists('roles', 'id')->where('tenant_id', $tenantId)],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('tenant_id', $tenantId)],
            'branch_role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where('tenant_id', $tenantId)],
        ]);

        if (!empty($data['branch_role_id']) && empty($data['branch_id'])) {
            return response()->json(['success' => false, 'message' => 'Şube rolü için şube seçilmelidir.'], 422);
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $branchRoleId = $data['branch_role_id'] ?? null;
        $branchId = $data['branch_id'] ?? null;
        unset($data['branch_role_id']);

        $user->update($data);

        if (!empty($branchId)) {
            $user->additionalRoles()->wherePivot('branch_id', $branchId)->detach();
            if (!empty($branchRoleId)) {
                $user->additionalRoles()->attach($branchRoleId, [
                    'tenant_id' => $tenantId,
                    'branch_id' => $branchId,
                    'created_at' => now(),
                ]);
            }
        }

        return response()->json(['success' => true, 'user' => $user->fresh()->load(['role', 'branch'])]);
    }

    public function destroy(User $user)
    {
        if ($user->tenant_id !== (int) session('tenant_id')) {
            return response()->json(['success' => false, 'message' => 'Yetkiniz yok.'], 403);
        }

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Kendinizi silemezsiniz.'], 422);
        }

        // Açık kasası olan kullanıcı silinemez (Z raporu / kasa geçmişini korur)
        if ($user->cashRegisters()->where('status', 'open')->exists()) {
            return response()->json(['success' => false, 'message' => 'Açık kasası olan kullanıcı silinemez.'], 422);
        }

        $user->delete();
        return response()->json(['success' => true]);
    }
}
