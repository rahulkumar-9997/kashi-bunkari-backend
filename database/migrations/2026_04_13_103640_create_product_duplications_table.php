<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_duplications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_product_id')
                  ->constrained('products')
                  ->onDelete('cascade');
            $table->foreignId('new_product_id')
                  ->constrained('products')
                  ->onDelete('cascade');       
            $table->timestamps();
            $table->index('original_product_id');
            $table->index('new_product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_duplications');
    }
};