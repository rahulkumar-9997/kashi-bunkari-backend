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
        Schema::table('blogs', function (Blueprint $table) {
            $table->string('reading_title')->nullable()->after('title');
            $table->string('tags')->nullable()->after('page_image');
            $table->unsignedBigInteger('view_count')->default(0)->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropColumn([
                'reading_title',
                'tags',
                'view_count',
            ]);
        });
    }
};
