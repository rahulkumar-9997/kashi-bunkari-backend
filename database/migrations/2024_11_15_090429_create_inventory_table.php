<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('mrp', 10, 2)->nullable();
            $table->decimal('purchase_rate', 10, 2)->nullable();
            $table->decimal('offer_rate', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'mrp']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventory');
    }
}
