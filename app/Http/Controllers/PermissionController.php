<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    public function index(Request $r)
    {
        $roles = Role::orderBy('id')->get();
        $role = $r->filled('role_id') ? Role::with('permissions')->findOrFail($r->integer('role_id')) : $roles->first()->load('permissions');

        return view('permissions.index', compact('roles', 'role'));
    }

    public function update(Request $r, Role $role)
    {
        abort_if($role->is_system, 403, 'Hak akses Administrator tidak dapat diubah.');
        $data = $r->validate(['permissions' => 'nullable|array', 'permissions.*' => 'string|exists:permissions,code']);
        $codes = $data['permissions'] ?? [];
        foreach ($codes as $code) {
            [$module] = explode('.', $code);
            if (isset(config('pos.modules')[$module]['actions']['view'])) {
                $codes[] = $module.'.view';
            }
        }
        $ids = Permission::whereIn('code', array_unique($codes))->pluck('id');
        DB::transaction(fn () => $role->permissions()->sync($ids));

        return redirect()->route('permissions.index', ['role_id' => $role->id])->with('success', 'Hak akses disimpan.');
    }
}
