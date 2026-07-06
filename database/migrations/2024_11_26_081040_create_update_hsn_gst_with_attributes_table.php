<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUpdateHsnGstWithAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('update_hsn_gst_with_attributes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')
                    ->constrained('category')
                    ->cascadeOnDelete();
                $table->foreignId('attributes_id')
                    ->constrained('attributes')
                    ->cascadeOnDelete();
                $table->foreignId('attributes_value_id')
                    ->constrained('attributes_value')
                    ->cascadeOnDelete();
                $table->string('hsn_code')->nullable();
                $table->string('gst_in_per')->nullable();
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
        Schema::dropIfExists('update_hsn_gst_with_attributes');
    }
}