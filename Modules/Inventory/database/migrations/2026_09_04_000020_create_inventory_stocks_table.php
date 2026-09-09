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
        if (! Schema::hasTable('inventory_stocks')) {
            Schema::create('inventory_stocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('tenant_branch_id')->constrained('tenant_branches')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();

                $table->integer('quantity_on_hand')->default(0);
                $table->integer('quantity_reserved')->default(0);
                $table->integer('reorder_level')->default(5);

                $table->timestamps();

                $table->unique(['tenant_branch_id', 'product_variant_id'], 'branch_variant_stock_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
