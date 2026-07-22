<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_shipment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->foreignId('order_status_id')
                ->nullable()
                ->constrained('order_statuses')
                ->nullOnDelete();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->string('tracking_no')->unique()->nullable();
            $table->string('courier_name')->nullable();
            $table->text('shipment_details')->nullable();
            $table->date('shipment_date')->nullable();
            $table->date('receiving_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipment_records');
    }
};