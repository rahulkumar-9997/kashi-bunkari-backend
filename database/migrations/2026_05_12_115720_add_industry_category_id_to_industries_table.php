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
        Schema::table('industries', function (Blueprint $table) {
            $table->foreignId('industry_category_id')
                ->nullable()
                ->after('id')
                ->constrained('industry_categories')
                ->cascadeOnDelete();           
            $table->index('industry_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('industries', function (Blueprint $table) {
            $table->dropForeign(['industry_category_id']);
            $table->dropColumn('industry_category_id');
        });
    }
};
