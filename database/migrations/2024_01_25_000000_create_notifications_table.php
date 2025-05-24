<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('notifications_elsid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users_elsid')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('type')->comment('order_status, payment_status, etc');
            $table->json('data')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders_elsid')->onDelete('cascade');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications_elsid');
    }
}
