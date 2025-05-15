<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersElsidTable extends Migration
{
    public function up()
    {
        Schema::create('orders_elsid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users_elsid')->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'expired', 'failed'])->default('unpaid');
            $table->string('payment_token')->nullable();
            $table->string('payment_url')->nullable();
            $table->text('shipping_address');
            $table->string('shipping_city', 100);
            $table->string('shipping_province', 100);
            $table->string('shipping_postal_code', 10);
            $table->decimal('shipping_cost', 10, 2);
            $table->string('courier', 100)->nullable();
            $table->string('courier_service', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders_elsid');
    }
}
