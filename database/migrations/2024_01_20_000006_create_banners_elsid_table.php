<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBannersElsidTable extends Migration
{
    public function up()
    {
        Schema::create('banners_elsid', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('banners_elsid');
    }
}
