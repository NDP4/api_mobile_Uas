<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWishlistsElsidTable extends Migration
{
    public function up()
    {
        Schema::create('wishlists_elsid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users_elsid')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products_elsid')->onDelete('cascade');
            $table->timestamps();
            // Ensure user can't add same product twice
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('wishlists_elsid');
    }
}
