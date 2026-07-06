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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamps();
            $table->index('customer_id');
            $table->index('session_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
