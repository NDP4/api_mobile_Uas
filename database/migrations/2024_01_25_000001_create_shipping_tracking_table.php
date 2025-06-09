<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingTrackingTable extends Migration
{
    public function up()
    {
        Schema::create('shipping_tracking_elsid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders_elsid')->onDelete('cascade');
            $table->string('courier');
            $table->string('service');
            $table->integer('etd_days');
            $table->string('status')->default('pending');
            $table->dateTime('shipping_start_date')->nullable();
            $table->dateTime('estimated_arrival')->nullable();
            $table->dateTime('actual_arrival')->nullable();
            $table->json('tracking_history')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shipping_tracking_elsid');
    }
}
