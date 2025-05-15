<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductVariantsElsidTable extends Migration
{
    public function up()
    {
        Schema::create('product_variants_elsid', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products_elsid')->onDelete('cascade');
            $table->string('variant_name');
            $table->decimal('price', 10, 2);
            $table->decimal('discount', 5, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variants_elsid');
    }
}
