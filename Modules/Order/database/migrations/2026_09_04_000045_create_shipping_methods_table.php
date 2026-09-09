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
        if (! Schema::hasTable('shipping_methods')) {
            Schema::create('shipping_methods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();

                $table->string('name', 150);
                $table->string('code', 50)->index();
                $table->string('carrier', 100); // FedEx, DHL, UPS, USPS, In-House, etc.
                $table->enum('rate_type', ['flat', 'tiered_weight', 'tiered_total', 'free'])->default('flat');
                $table->decimal('base_rate', 12, 4)->default(0.0000);
                $table->decimal('free_shipping_threshold', 12, 4)->nullable();
                $table->unsignedInteger('min_days')->default(1);
                $table->unsignedInteger('max_days')->default(5);
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->json('settings')->nullable(); // e.g. weight brackets or destination restrictions

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'is_active']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
