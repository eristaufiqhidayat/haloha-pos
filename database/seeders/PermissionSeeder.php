<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('pos.modules') as $module => $def) {
            foreach ($def['actions'] as $action => $label) {
                Permission::firstOrCreate(['code' => $module.'.'.$action], ['name' => $def['label'].' — '.$label]);
            }
        }
    }
}
