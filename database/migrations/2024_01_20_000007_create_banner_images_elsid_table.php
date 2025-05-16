<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBannerImagesElsidTable extends Migration
{
    public function up()
    {
        Schema::create('banner_images_elsid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_id')->constrained('banners_elsid')->onDelete('cascade');
            $table->string('image_url');
            $table->integer('image_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('banner_images_elsid');
    }
}
