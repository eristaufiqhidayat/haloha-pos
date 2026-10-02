<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'Administrator'], ['description' => 'Seluruh pengelolaan toko', 'is_active' => true]);
        if (! $admin->is_system) {
            $admin->is_system = true;
            $admin->save();
        }$admin->permissions()->sync(Permission::pluck('id'));
        foreach (['Kasir' => ['pos.view', 'pos.create', 'inventory.view'], 'Gudang' => ['products.view', 'products.create', 'products.update', 'categories.view', 'stock.view', 'stock.create', 'inventory.view', 'inventory.export'], 'Pemilik' => ['dashboard.view', 'sales.view', 'sales.export', 'inventory.view', 'inventory.export']] as $name => $codes) {
            $role = Role::firstOrCreate(['name' => $name], ['is_active' => true]);
            if ($role->wasRecentlyCreated) {
                $role->permissions()->sync(Permission::whereIn('code', $codes)->pluck('id'));
            }
        }
    }
}
