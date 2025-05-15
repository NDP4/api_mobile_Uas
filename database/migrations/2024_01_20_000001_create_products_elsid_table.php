<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsElsidTable extends Migration
{
    public function up()
    {
        Schema::create('products_elsid', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 5, 2)->default(0);
            $table->integer('main_stock')->default(0);
            $table->integer('weight')->default(0);
            $table->enum('status', ['available', 'unavailable'])->default('available');
            $table->boolean('has_variants')->default(false);
            $table->integer('purchase_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products_elsid');
    }
}
