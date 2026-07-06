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
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->tinyInteger('rating_star_value');
            $table->string('review_title')->nullable();
            $table->text('review_message')->nullable();
            $table->string('review_name')->nullable();
            $table->string('review_email')->nullable();
            $table->boolean('status')->default(0);
            $table->timestamp('review_post_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
