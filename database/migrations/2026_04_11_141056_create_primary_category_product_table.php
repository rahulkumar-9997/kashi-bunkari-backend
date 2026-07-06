<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('primary_category_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('primary_category_id');
            $table->unsignedBigInteger('product_id');
            $table->foreign('primary_category_id')
                  ->references('id')
                  ->on('primary_categories')
                  ->onDelete('cascade');
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('primary_category_product');
    }
};
