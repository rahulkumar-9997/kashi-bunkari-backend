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
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('locality')->nullable()->after('zip_code');
            $table->string('landmark')->nullable()->after('locality');
            $table->string('alternate_phone')->nullable()->after('landmark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn([
                'locality',
                'landmark',
                'alternate_phone',
            ]);
        });
    }
};
