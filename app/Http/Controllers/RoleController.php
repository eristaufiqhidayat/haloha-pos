<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function index()
    {
        return view('roles.index', ['roles' => Role::withCount('users')->orderBy('id')->get()]);
    }

    public function create()
    {
        return view('roles.form', ['role' => new Role(['is_active' => true])]);
    }

    public function edit(Role $role)
    {
        abort_if($role->is_system, 403, 'Kategori Administrator dilindungi.');

        return view('roles.form', compact('role'));
    }

    private function data(Request $r, ?Role $role = null)
    {
        return $r->validate(['name' => ['required', 'string', 'max:80', Rule::unique('roles')->ignore($role?->id)], 'description' => 'nullable|string|max:500', 'is_active' => 'required|boolean']);
    }

    public function store(Request $r)
    {
        $role = Role::create($this->data($r));

        return redirect()->route('roles.index')->with('success', 'Kategori dibuat. Atur hak akses sebelum digunakan.');
    }

    public function update(Request $r, Role $role)
    {
        abort_if($role->is_system, 403);
        $role->update($this->data($r, $role));

        return redirect()->route('roles.index')->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Role $role)
    {
        abort_if($role->is_system, 403);
        DB::transaction(function () use ($role) {
            $locked = Role::whereKey($role->id)->lockForUpdate()->firstOrFail();
            if (User::withTrashed()->where('role_id', $role->id)->exists()) {
                throw ValidationException::withMessages(['role' => 'Kategori masih digunakan pengguna (termasuk arsip).']);
            }$locked->delete();
        }, 3);

        return back()->with('success','Kategori dihapus.');
    }
}
