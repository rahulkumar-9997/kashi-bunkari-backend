<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('category_id')
                ->constrained('category')
                ->cascadeOnDelete();
            $table->foreignId('subcategory_id')
                ->nullable()
                ->constrained('sub_category')
                ->nullOnDelete();
            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brand')
                ->nullOnDelete();
            $table->foreignId('label_id')
                ->nullable()
                ->constrained('label')
                ->nullOnDelete();
            $table->decimal('product_weight', 8, 2)->nullable();
            $table->boolean('product_stock_status')->default(1);
            $table->decimal('product_price', 8, 2)->nullable();
            $table->decimal('product_sale_price', 8, 2)->nullable();
            $table->boolean('product_status')->default(1);
            $table->boolean('warranty_status')->default(0);
            $table->boolean('attributes_show_status')->default(1);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->longText('product_description')->nullable();
            $table->longText('product_specification')->nullable();
            $table->timestamp('product_add_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
