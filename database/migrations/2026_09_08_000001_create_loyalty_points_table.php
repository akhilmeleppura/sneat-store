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
        if (!Schema::hasTable('loyalty_points_ledger')) {
            Schema::create('loyalty_points_ledger', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->integer('points_change'); // positive for earned, negative for redeemed
                $table->integer('balance_after');
                $table->string('type', 50)->default('purchase'); // purchase, redemption, bonus, refund
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_points_ledger');
    }
};
