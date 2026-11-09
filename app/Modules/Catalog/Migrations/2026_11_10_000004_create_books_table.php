<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publisher_id')->nullable()->constrained()->onDelete('set null');
            $table->string('title');
            $table->string('isbn', 13)->unique(); // disimpan dalam format kanonik ISBN-13
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
