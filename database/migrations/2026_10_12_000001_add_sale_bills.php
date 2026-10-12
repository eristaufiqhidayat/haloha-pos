<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_bills', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('number');
            $t->string('label', 40)->nullable();
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('discount')->default(0);
            $t->unsignedBigInteger('tax_amount')->default(0);
            $t->unsignedBigInteger('total');
            $t->string('status', 20)->default('unpaid');
            $t->string('payment_method', 20)->nullable();
            $t->unsignedBigInteger('paid_amount')->default(0);
            $t->unsignedBigInteger('change_amount')->default(0);
            $t->dateTime('paid_at')->nullable();
            $t->foreignId('paid_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['sale_id', 'number']);
        });
        Schema::table('sales', fn (Blueprint $t) => $t->string('split_mode', 10)->nullable());
        Schema::table('sale_items', fn (Blueprint $t) => $t->foreignId('sale_bill_id')->nullable()->constrained()->nullOnDelete());
        Schema::table('sale_payments', function (Blueprint $t) {
            $t->foreignId('sale_bill_id')->nullable()->constrained()->cascadeOnDelete();
            // 0 = pembayaran seluruh pesanan; 1..15 = nomor tagihan split.
            $t->unsignedTinyInteger('bill_number')->default(0);
            // Tambah indeks baru dulu: di MySQL indeks lama dipakai foreign key sale_id.
            $t->unique(['sale_id', 'bill_number', 'method']);
            $t->dropUnique(['sale_id', 'method']);
        });
    }

    public function down(): void
    {
        Schema::table('sale_payments', function (Blueprint $t) {
            $t->unique(['sale_id', 'method']);
            $t->dropUnique(['sale_id', 'bill_number', 'method']);
            $t->dropConstrainedForeignId('sale_bill_id');
            $t->dropColumn('bill_number');
        });
        Schema::table('sale_items', fn (Blueprint $t) => $t->dropConstrainedForeignId('sale_bill_id'));
        Schema::table('sales', fn (Blueprint $t) => $t->dropColumn('split_mode'));
        Schema::dropIfExists('sale_bills');
    }
};
