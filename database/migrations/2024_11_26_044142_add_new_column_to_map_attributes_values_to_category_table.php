<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnToMapAttributesValuesToCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('map_attributes_values_to_category', function (Blueprint $table) {
            $table->foreignId('attributes_id')->nullable()->after('attributes_value_id')->constrained('attributes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('map_attributes_values_to_category', function (Blueprint $table) {
            //
        });
    }
}
