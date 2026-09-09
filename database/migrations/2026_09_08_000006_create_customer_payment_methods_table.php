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
        if (!Schema::hasTable('customer_payment_methods')) {
            Schema::create('customer_payment_methods', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('gateway', 50)->default('stripe'); // stripe, paypal, etc.
                $table->string('payment_method_token', 255)->index();
                $table->string('card_brand', 30)->nullable(); // visa, mastercard, amex
                $table->string('card_last_four', 4)->nullable();
                $table->string('card_exp_month', 2)->nullable();
                $table->string('card_exp_year', 4)->nullable();
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_payment_methods');
    }
};
