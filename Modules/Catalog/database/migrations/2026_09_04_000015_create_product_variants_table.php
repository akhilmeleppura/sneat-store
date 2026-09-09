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
        if (! Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

                $table->string('sku')->index();
                $table->string('barcode')->nullable()->index();

                $table->decimal('price', 12, 4);
                $table->decimal('compare_at_price', 12, 4)->nullable();
                $table->decimal('cost_price', 12, 4)->nullable();

                $table->decimal('weight', 8, 2)->nullable();
                $table->json('dimensions')->nullable();
                $table->string('image_url')->nullable();

                $table->enum('status', ['active', 'inactive'])->default('active')->index();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'sku']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
