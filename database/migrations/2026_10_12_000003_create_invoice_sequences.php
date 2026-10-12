<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris per tanggal; dikunci saat membuat invoice supaya nomor urut tidak bentrok antar kasir.
        Schema::create('invoice_sequences', function (Blueprint $t) {
            $t->date('date')->primary();
            $t->unsignedInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_sequences');
    }
};
