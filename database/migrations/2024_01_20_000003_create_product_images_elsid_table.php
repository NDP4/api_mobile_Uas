<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductImagesElsidTable extends Migration
{
    public function up()
    {
        Schema::create('product_images_elsid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products_elsid')->onDelete('cascade');
            $table->string('image_url');
            $table->integer('image_order')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_images_elsid');
    }
}
