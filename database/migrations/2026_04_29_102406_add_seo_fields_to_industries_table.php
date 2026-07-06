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
            $table->string('meta_title')->nullable()->after('sort_order');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->text('short_description')->nullable()->after('meta_description');
            $table->longText('long_description')->nullable()->after('short_description');
            $table->boolean('status')->default(1)->after('long_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('industries', function (Blueprint $table) {
            $table->dropColumn([
                'meta_title',
                'meta_description',
                'short_description',
                'long_description',
                'status'
            ]);
        });
    }
};
