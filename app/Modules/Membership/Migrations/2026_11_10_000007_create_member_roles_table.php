<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->unsignedInteger('max_loans')->default(3);          // batas buku yang boleh dipinjam
            $table->unsignedInteger('loan_period_days')->default(14);  // lama peminjaman (hari)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_roles');
    }
};
