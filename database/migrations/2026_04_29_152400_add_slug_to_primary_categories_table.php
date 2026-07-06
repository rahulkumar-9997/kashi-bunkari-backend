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
        Schema::table('primary_categories', function (Blueprint $table) {
            $table->string('additional_slug')->nullable()->after('slug');
            $table->index('additional_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('primary_categories', function (Blueprint $table) {
            $table->dropColumn('additional_slug');
        });
    }
};
