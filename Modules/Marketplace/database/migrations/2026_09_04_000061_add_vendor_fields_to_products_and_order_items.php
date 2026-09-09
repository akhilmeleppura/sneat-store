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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('brand_id')->index();
                $table->foreign('vendor_id')->references('id')->on('marketplace_vendors')->onDelete('set null');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('product_variant_id')->index();
                $table->decimal('vendor_commission_rate', 5, 2)->nullable()->after('line_total');
                $table->decimal('vendor_commission_amount', 10, 2)->nullable()->after('vendor_commission_rate');
                $table->decimal('vendor_earnings_amount', 10, 2)->nullable()->after('vendor_commission_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'vendor_id')) {
                $table->dropForeign(['vendor_id']);
                $table->dropColumn('vendor_id');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'vendor_id',
                'vendor_commission_rate',
                'vendor_commission_amount',
                'vendor_earnings_amount',
            ]);
        });
    }
};
