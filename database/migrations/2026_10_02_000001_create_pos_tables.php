<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->text('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->boolean('is_system')->default(false);
            $t->timestamps();
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
        });
        Schema::create('permission_role', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $t->primary(['role_id', 'permission_id']);
        });
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('role_id')->nullable()->constrained()->restrictOnDelete();
            $t->boolean('is_active')->default(true);
            $t->softDeletes();
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->string('sku')->unique();
            $t->string('name');
            $t->unsignedBigInteger('cost_price');
            $t->unsignedBigInteger('selling_price');
            $t->unsignedInteger('stock')->default(0);
            $t->unsignedInteger('minimum_stock')->default(8);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['is_active', 'name']);
        });
        Schema::create('sales', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_number')->unique();
            $t->uuid('checkout_token')->unique();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->dateTime('sold_at')->index();
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('discount')->default(0);
            $t->unsignedBigInteger('total');
            $t->unsignedBigInteger('total_cost');
            $t->string('payment_method', 20);
            $t->unsignedBigInteger('paid_amount');
            $t->unsignedBigInteger('change_amount')->default(0);
            $t->timestamps();
        });
        Schema::create('sale_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->string('product_name');
            $t->string('sku');
            $t->unsignedInteger('quantity');
            $t->unsignedBigInteger('unit_price');
            $t->unsignedBigInteger('unit_cost');
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('total_cost');
        });
        Schema::create('stock_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('sale_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('type', 20);
            $t->integer('quantity');
            $t->unsignedInteger('stock_after');
            $t->text('note')->nullable();
            $t->dateTime('occurred_at');
            $t->index(['product_id', 'occurred_at']);
            $t->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('role_id');
            $t->dropColumn(['is_active', 'deleted_at']);
        });
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
