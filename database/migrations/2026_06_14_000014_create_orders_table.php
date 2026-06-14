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
            $table->foreignId('buyer_id')->constrained('users');
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('address_id')->constrained('addresses');
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers');
            $table->foreignId('promo_id')->nullable()->constrained('promos');
            $table->string('shipping_recipient_name', 100);
            $table->string('shipping_phone', 20);
            $table->text('shipping_address');
            $table->enum('delivery_method', ['INSTANT', 'NEXT_DAY', 'REGULAR']);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('delivery_fee', 15, 2);
            $table->decimal('ppn_amount', 15, 2);
            $table->decimal('final_total', 15, 2);
            $table->enum('status', ['PACKAGING', 'WAITING_FOR_DRIVER', 'IN_DELIVERY', 'COMPLETED', 'RETURNED']);
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('expired_at');
            $table->dateTime('returned_at')->nullable();
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
