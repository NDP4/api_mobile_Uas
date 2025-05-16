<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductReviewsElsidTable extends Migration
{
    public function up()
    {
        Schema::create('product_reviews_elsid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products_elsid')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users_elsid')->onDelete('cascade');
            $table->integer('rating')->unsigned();
            $table->text('review')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_reviews_elsid');
    }
}
