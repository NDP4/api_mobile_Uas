<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryAndPaymentMethodToOrdersElsid extends Migration
{
    public function up()
    {
        Schema::table('orders_elsid', function (Blueprint $table) {
            // Add payment_method column
            $table->enum('payment_method', ['online', 'cod'])->default('online')->after('payment_status');

            // Add estimated delivery columns
            $table->integer('estimated_days')->nullable()->after('payment_method');
            $table->timestamp('delivery_start_time')->nullable()->after('estimated_days');
            $table->timestamp('estimated_delivery_time')->nullable()->after('delivery_start_time');

            // Update status enum
            DB::statement("ALTER TABLE orders_elsid MODIFY COLUMN status ENUM('pending', 'processing', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending'");

            // Update payment_status enum
            DB::statement("ALTER TABLE orders_elsid MODIFY COLUMN payment_status ENUM('unpaid', 'pending', 'paid', 'expired', 'failed') DEFAULT 'unpaid'");
        });
    }

    public function down()
    {
        Schema::table('orders_elsid', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn('payment_method');
            $table->dropColumn('estimated_days');
            $table->dropColumn('delivery_start_time');
            $table->dropColumn('estimated_delivery_time');

            // Revert status enum
            DB::statement("ALTER TABLE orders_elsid MODIFY COLUMN status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending'");

            // Revert payment_status enum
            DB::statement("ALTER TABLE orders_elsid MODIFY COLUMN payment_status ENUM('unpaid', 'paid', 'expired', 'failed') DEFAULT 'unpaid'");
        });
    }
}
