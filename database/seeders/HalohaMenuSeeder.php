<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HalohaMenuSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(HalohaMenuProductSeeder::class);
    }
}
