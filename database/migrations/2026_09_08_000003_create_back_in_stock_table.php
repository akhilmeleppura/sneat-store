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
        if (!Schema::hasTable('back_in_stock_subscriptions')) {
            Schema::create('back_in_stock_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('variant_id')->nullable()->index();
                $table->string('email', 255);
                $table->string('phone', 50)->nullable();
                $table->boolean('is_notified')->default(false);
                $table->dateTime('notified_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('back_in_stock_subscriptions');
    }
};
