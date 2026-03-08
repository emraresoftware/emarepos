<?php
namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['role', 'branch'])->orderBy('name');

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
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('pos.users.index', compact('users', 'roles', 'branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'nullable|integer|exists:roles,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $data['tenant_id'] = session('tenant_id');
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        return response()->json(['success' => true, 'user' => $user->load(['role', 'branch'])]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role_id' => 'nullable|integer|exists:roles,id',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return response()->json(['success' => true, 'user' => $user->fresh()->load(['role', 'branch'])]);
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Kendinizi silemezsiniz.'], 422);
        }
        $user->delete();
        return response()->json(['success' => true]);
    }
}
