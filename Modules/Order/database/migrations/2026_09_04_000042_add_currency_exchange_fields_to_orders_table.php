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
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'exchange_rate')) {
                $table->decimal('exchange_rate', 14, 6)->default(1.000000)->after('currency');
            }
            if (! Schema::hasColumn('orders', 'base_currency')) {
                $table->string('base_currency', 10)->default('USD')->after('exchange_rate');
            }
            if (! Schema::hasColumn('orders', 'base_grand_total')) {
                $table->decimal('base_grand_total', 12, 4)->nullable()->after('base_currency');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'base_grand_total')) {
                $table->dropColumn('base_grand_total');
            }
            if (Schema::hasColumn('orders', 'base_currency')) {
                $table->dropColumn('base_currency');
            }
            if (Schema::hasColumn('orders', 'exchange_rate')) {
                $table->dropColumn('exchange_rate');
            }
        });
    }
};
