<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', fn (Blueprint $t) => $t->boolean('takeaway')->default(false));
        // Pesanan lama yang take away: semua menunya dianggap take away.
        DB::table('sale_items')->whereIn('sale_id', DB::table('sales')->where('takeaway', true)->select('id'))->update(['takeaway' => true]);
    }

    public function down(): void
    {
        Schema::table('sale_items', fn (Blueprint $t) => $t->dropColumn('takeaway'));
    }
};
