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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->timestamp('order_date')->useCurrent();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            $table->enum('payment_mode', ['cod', 'razorpay', 'stripe', 'paypal']);
            $table->boolean('payment_received')->default(false);
            $table->text('payment_fail_reason')->nullable();
            $table->foreignId('customer_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('shipping_address_id')
                  ->nullable()
                  ->constrained('order_addresses')
                  ->nullOnDelete();
            $table->foreignId('billing_address_id')
                  ->nullable()
                  ->constrained('order_addresses')
                  ->nullOnDelete();
            $table->foreignId('order_status_id')
                  ->nullable()
                  ->constrained('order_statuses')
                  ->nullOnDelete();
            $table->text('order_cancel_reason')->nullable();
            $table->string('coupon_code')->nullable();
            $table->decimal('coupon_discount_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'order_date']);
            $table->index('order_date');
            $table->index('payment_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
