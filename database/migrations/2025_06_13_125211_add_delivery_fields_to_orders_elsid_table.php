<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('orders_elsid', function (Blueprint $table) {
            $table->integer('estimated_days')->nullable()->after('courier_service');
            $table->dateTime('delivery_start_time')->nullable()->after('estimated_days');
            $table->dateTime('estimated_delivery_time')->nullable()->after('delivery_start_time');
        });
    }

    public function down()
    {
        Schema::table('orders_elsid', function (Blueprint $table) {
            $table->dropColumn(['estimated_days', 'delivery_start_time', 'estimated_delivery_time']);
        });
    }
};
