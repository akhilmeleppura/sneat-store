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
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
                $table->foreignId('tenant_branch_id')->nullable()->constrained('tenant_branches')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

                $table->string('order_number', 50)->unique()->index();
                $table->string('customer_name');
                $table->string('customer_email');
                $table->string('customer_phone')->nullable();

                $table->json('shipping_address')->nullable();
                $table->json('billing_address')->nullable();

                $table->decimal('subtotal', 12, 4);
                $table->decimal('discount_amount', 12, 4)->default(0.0000);
                $table->decimal('tax_amount', 12, 4)->default(0.0000);
                $table->decimal('shipping_amount', 12, 4)->default(0.0000);
                $table->decimal('grand_total', 12, 4);
                $table->string('currency', 10)->default('USD');

                $table->enum('status', ['pending', 'processing', 'completed', 'cancelled', 'refunded'])->default('pending')->index();
                $table->enum('payment_status', ['unpaid', 'paid', 'partially_paid', 'refunded', 'failed'])->default('unpaid')->index();
                $table->string('payment_method', 50)->default('cod');
                $table->enum('fulfillment_status', ['unfulfilled', 'fulfilled', 'cancelled'])->default('unfulfilled')->index();

                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
