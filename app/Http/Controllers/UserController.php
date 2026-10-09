<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', ['users' => User::with('role')->orderBy('name')->paginate(20)]);
    }

    public function create()
    {
        return $this->form(new User(['is_active' => true]));
    }

    public function edit(User $user)
    {
        return $this->form($user);
    }

    private function form(User $user)
    {
        return view('users.form', ['user' => $user, 'roles' => Role::where('is_active', true)->orWhere('id', $user->role_id)->orderBy('name')->get()]);
    }

    private function data(Request $r, ?User $user = null)
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user?->id)], 'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:128', 'confirmed'], 'role_id' => 'required|exists:roles,id', 'is_active' => 'required|boolean']);
        if (! Role::findOrFail($data['role_id'])->is_active) {
            throw ValidationException::withMessages(['role_id' => 'Kategori pengguna nonaktif.']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }

return $data;
    }

    public function store(Request $r)
    {
        User::create($this->data($r) + ['product_ids' => []]);

        return redirect()->route('users.index')->with('success', 'Pengguna dibuat.');
    }

    public function update(Request $r, User $user)
    {
        $data = $this->data($r, $user);
        DB::transaction(function () use ($user, $data) {
            $adminRole = Role::where('is_system', true)->lockForUpdate()->firstOrFail();
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($locked->role_id === $adminRole->id && $locked->is_active && (! $data['is_active'] || (int) $data['role_id'] !== $adminRole->id) && User::where('role_id', $adminRole->id)->where('is_active', true)->count() <= 1) {
                throw ValidationException::withMessages(['role_id' => 'Minimal satu administrator aktif harus dipertahankan.']);
            }
            $locked->update($data);
        }, 3);

        return redirect()->route('users.index')->with('success', 'Pengguna diperbarui.');
    }

    public function destroy(Request $r, User $user)
    {
        if ($r->user()->id === $user->id) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun yang sedang digunakan.']);
        }
        DB::transaction(function () use ($user) {
            $role = Role::where('is_system', true)->lockForUpdate()->firstOrFail();
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($locked->role_id === $role->id && $locked->is_active && User::where('role_id', $role->id)->where('is_active', true)->count() <= 1) {
                throw ValidationException::withMessages(['user' => 'Administrator aktif terakhir tidak dapat dihapus.']);
            }$locked->update(['is_active' => false]);
            $locked->delete();
        }, 3);

        return back()->with('success','Pengguna dihapus; riwayat transaksi tetap tersedia.');
    }
}
