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
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();

                $table->string('name');
                $table->string('slug')->index();
                $table->string('sku')->nullable()->index();
                $table->string('barcode')->nullable()->index();
                $table->enum('type', ['simple', 'variable', 'digital'])->default('simple');

                $table->decimal('price', 12, 4)->default(0.0000);
                $table->decimal('compare_at_price', 12, 4)->nullable();
                $table->decimal('cost_price', 12, 4)->nullable();
                $table->string('currency', 10)->default('USD');

                $table->text('short_description')->nullable();
                $table->longText('description')->nullable();

                $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->index();
                $table->boolean('is_featured')->default(false)->index();
                $table->boolean('has_variants')->default(false);

                // SEO
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords')->nullable();

                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'slug']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
