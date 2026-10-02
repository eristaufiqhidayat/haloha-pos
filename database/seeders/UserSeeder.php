<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('POS_SEED_PASSWORD');
        if (! $password && app()->environment('production')) {
            throw new \RuntimeException('Atur POS_SEED_PASSWORD sebelum seeding di production.');
        }$password = $password ?: 'Haloha123!';
        foreach ([['Administrator', 'Admin HALOHA', 'admin@haloha.test'], ['Kasir', 'Kasir 01', 'kasir@haloha.test'], ['Gudang', 'Petugas Gudang', 'gudang@haloha.test'], ['Pemilik', 'Pemilik Toko', 'pemilik@haloha.test']] as [$role,$name,$email]) {
            User::firstOrCreate(['email' => $email], ['name' => $name, 'password' => $password, 'role_id' => Role::where('name', $role)->firstOrFail()->id, 'is_active' => true]);
        }
    }
}
