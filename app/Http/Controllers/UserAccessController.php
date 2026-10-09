<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserAccessController extends Controller
{
    public function edit(Request $r, User $user)
    {
        abort_unless($r->user()->role->is_system, 403);
        return view('users.access', ['user' => $user->load('role'), 'products' => Product::with('category')->orderBy('name')->get()]);
    }

    public function update(Request $r, User $user)
    {
        abort_unless($r->user()->role->is_system && ! $user->role->is_system, 403);
        $codes = [];
        foreach (config('pos.modules') as $module => $config) {
            foreach ($config['actions'] as $action => $label) {
                $codes[] = $module.'.'.$action;
            }
        }
        $data = $r->validate([
            'inherit_permissions' => 'required|boolean', 'permission_codes' => 'nullable|array',
            'permission_codes.*' => ['required', 'distinct', Rule::in($codes)],
            'product_ids' => 'nullable|array', 'product_ids.*' => 'required|integer|distinct|exists:products,id',
        ]);
        $permissions = $data['permission_codes'] ?? [];
        foreach ($permissions as $code) {
            $module = explode('.', $code)[0];
            $permissions[] = $module.'.view';
        }
        DB::transaction(fn () => $user->update([
            'permission_codes' => $data['inherit_permissions'] ? null : array_values(array_unique($permissions)),
            'product_ids' => array_map('intval', $data['product_ids'] ?? []),
        ]));
        return redirect()->route('users.index')->with('success', 'Akses '.$user->name.' diperbarui.');
    }
}
