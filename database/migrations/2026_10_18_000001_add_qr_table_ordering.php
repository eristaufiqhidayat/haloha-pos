<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dining_tables', function (Blueprint $t) {
            $t->id();
            $t->string('number', 30)->unique();
            $t->string('description', 100)->nullable();
            // Token acak di URL QR; nomor meja saja mudah ditebak.
            $t->string('token', 40)->unique();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('app_settings', function (Blueprint $t) {
            $t->string('key', 60)->primary();
            $t->text('value')->nullable();
            $t->timestamps();
        });
        // Pesanan dari QR belum punya kasir sampai dikonfirmasi.
        Schema::table('sales', fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable()->change());
        Schema::table('sales', function (Blueprint $t) {
            $t->string('source', 10)->default('pos');
            $t->foreignId('dining_table_id')->nullable()->constrained()->nullOnDelete();
            $t->string('qr_payment', 10)->nullable();
            $t->unsignedSmallInteger('qris_code')->default(0);
            $t->timestamp('qris_claimed_at')->nullable();
            $t->timestamp('confirmed_at')->nullable();
        });
        foreach (['view' => 'Lihat', 'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Hapus'] as $action => $label) {
            DB::table('permissions')->insertOrIgnore(['code' => 'tables.'.$action, 'name' => 'Meja & QR — '.$label]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('code', 'like', 'tables.%')->delete();
        Schema::table('sales', function (Blueprint $t) {
            $t->dropConstrainedForeignId('dining_table_id');
            $t->dropColumn(['source', 'qr_payment', 'qris_code', 'qris_claimed_at', 'confirmed_at']);
        });
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('dining_tables');
    }
};
