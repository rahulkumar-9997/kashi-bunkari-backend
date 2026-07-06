<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMappedCategoryToAttributesForFrontTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mapped_category_to_attributes_for_front', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('category')
                ->cascadeOnDelete();
            $table->foreignId('attributes_id')
                ->constrained('attributes')
                ->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->integer('status')->default(1);
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
        Schema::dropIfExists('mapped_category_to_attributes_for_front');
    }
}
