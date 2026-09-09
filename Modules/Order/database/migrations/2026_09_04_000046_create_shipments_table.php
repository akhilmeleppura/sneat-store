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
        if (! Schema::hasTable('shipments')) {
            Schema::create('shipments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('shipping_method_id')->nullable()->constrained('shipping_methods')->nullOnDelete();

                $table->string('shipment_number', 50)->unique()->index();
                $table->string('tracking_number', 100)->nullable()->index();
                $table->string('carrier', 100)->nullable();
                $table->text('tracking_url')->nullable();
                $table->enum('status', [
                    'pending',
                    'processing',
                    'dispatched',
                    'in_transit',
                    'out_for_delivery',
                    'delivered',
                    'failed',
                    'returned',
                ])->default('pending')->index();

                $table->timestamp('shipped_at')->nullable();
                $table->timestamp('estimated_delivery_at')->nullable();
                $table->timestamp('delivered_at')->nullable();

                $table->string('recipient_name')->nullable();
                $table->json('delivery_address')->nullable();
                $table->json('timeline')->nullable();
                $table->text('notes')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
