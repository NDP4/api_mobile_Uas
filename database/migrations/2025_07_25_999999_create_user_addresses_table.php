<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAddressesTable extends Migration
{
    public function up()
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('label')->nullable(); // Contoh: Rumah, Kantor
            $table->string('recipient_name');
            $table->string('phone', 20);
            $table->text('address');
            $table->string('province', 100);
            $table->string('city', 100);
            $table->string('postal_code', 10);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users_elsid')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_addresses');
    }
}
