<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Many-to-Many Buku <-> Kategori dengan kolom pivot added_at
        Schema::create('book_book_category', function (Blueprint $table) {
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_category_id')->constrained('book_categories')->onDelete('cascade');
            $table->timestamp('added_at')->useCurrent();
            $table->primary(['book_id', 'book_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_book_category');
    }
};
