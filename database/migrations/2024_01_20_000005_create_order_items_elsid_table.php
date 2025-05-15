<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderItemsElsidTable extends Migration
{
    public function up()
    {
        Schema::create('order_items_elsid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders_elsid')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products_elsid')->onDelete('restrict');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants_elsid')->onDelete('restrict');
            $table->integer('quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items_elsid');
    }
}
