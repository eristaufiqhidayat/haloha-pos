<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->json('permission_codes')->nullable();
            $t->json('product_ids')->nullable();
        });
        // Preserve the existing catalog for existing accounts; new products require a grant.
        $ids = DB::table('products')->pluck('id')->map(fn ($id) => (int) $id)->all();
        DB::table('users')->update(['product_ids' => json_encode($ids)]);
        Schema::table('sales', function (Blueprint $t) {
            $t->string('status', 20)->default('paid')->index();
            $t->string('cashier_name')->nullable();
            $t->string('guest_name', 100)->nullable();
            $t->string('table_number', 30)->nullable();
            $t->boolean('takeaway')->default(false);
            $t->boolean('discount_enabled')->default(false);
            $t->boolean('tax_enabled')->default(false);
            $t->unsignedInteger('tax_rate')->default(0);
            $t->unsignedBigInteger('tax_amount')->default(0);
        });
        Schema::table('sale_items', fn (Blueprint $t) => $t->text('notes')->nullable());
        Schema::create('sale_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $t->string('method', 20);
            $t->unsignedBigInteger('amount');
            $t->unsignedBigInteger('paid_amount');
            $t->unsignedBigInteger('change_amount')->default(0);
            $t->timestamps();
            $t->unique(['sale_id', 'method']);
        });
        DB::table('sales')->orderBy('id')->chunkById(200, function ($sales) {
            foreach ($sales as $sale) {
                DB::table('sales')->where('id', $sale->id)->update([
                    'cashier_name' => DB::table('users')->where('id', $sale->user_id)->value('name'),
                    'discount_enabled' => $sale->discount > 0,
                ]);
                DB::table('sale_payments')->insert([
                    'sale_id' => $sale->id, 'method' => $sale->payment_method,
                    'amount' => $sale->total, 'paid_amount' => $sale->paid_amount,
                    'change_amount' => $sale->change_amount, 'created_at' => $sale->created_at,
                    'updated_at' => $sale->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
        Schema::table('sale_items', fn (Blueprint $t) => $t->dropColumn('notes'));
        Schema::table('sales', function (Blueprint $t) {
            $t->dropIndex(['status']);
            $t->dropColumn(['status', 'cashier_name', 'guest_name', 'table_number', 'takeaway', 'discount_enabled', 'tax_enabled', 'tax_rate', 'tax_amount']);
        });
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['permission_codes', 'product_ids']));
    }
};
