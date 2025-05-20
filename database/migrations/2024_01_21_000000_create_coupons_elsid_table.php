<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponsElsidTable extends Migration
{
    public function up()
    {
        Schema::create('coupons_elsid', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->decimal('discount_amount', 10, 2);
            $table->enum('discount_type', ['fixed', 'percentage']);
            $table->decimal('min_purchase', 10, 2)->default(0);
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create table for tracking coupon usage by users
        Schema::create('coupon_usage_elsid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons_elsid')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users_elsid')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders_elsid')->onDelete('cascade');
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupon_usage_elsid');
        Schema::dropIfExists('coupons_elsid');
    }
}
