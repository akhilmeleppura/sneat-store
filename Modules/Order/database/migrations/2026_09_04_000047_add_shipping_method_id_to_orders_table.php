<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'shipping_method_id')) {
                    $table->foreignId('shipping_method_id')
                        ->nullable()
                        ->after('fulfillment_status')
                        ->constrained('shipping_methods')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('orders', 'estimated_delivery_date')) {
                    $table->date('estimated_delivery_date')->nullable()->after('shipping_method_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'shipping_method_id')) {
                    $table->dropForeign(['shipping_method_id']);
                    $table->dropColumn('shipping_method_id');
                }

                if (Schema::hasColumn('orders', 'estimated_delivery_date')) {
                    $table->dropColumn('estimated_delivery_date');
                }
            });
        }
    }
};
