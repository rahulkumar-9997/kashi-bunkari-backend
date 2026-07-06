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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('customer_id')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('otp')->nullable();
            $table->string('google_id')->nullable();
            $table->string('profile_img')->nullable();
            $table->string('phone_number')->unique();
            $table->boolean('status')->default(true);
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('bio', 500)->nullable();
            $table->integer('login_attempts')->default(0);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
