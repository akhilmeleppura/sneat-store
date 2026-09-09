<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure a default tenant exists to backfill legacy catalog data
        $defaultTenantId = DB::table('tenants')->where('slug', 'default')->value('id');
        if (! $defaultTenantId) {
            $defaultTenantId = DB::table('tenants')->insertGetId([
                'name'       => 'AK-Mart',
                'slug'       => 'default',
                'status'     => 'active',
                'timezone'   => 'UTC',
                'currency'   => 'USD',
                'locale'     => 'en',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Ensure default store exists
        $defaultStoreId = DB::table('stores')->where('tenant_id', $defaultTenantId)->value('id');
        if (! $defaultStoreId) {
            $defaultStoreId = DB::table('stores')->insertGetId([
                'tenant_id'  => $defaultTenantId,
                'name'       => 'AK-Mart Main Store',
                'slug'       => 'main',
                'is_default' => 1,
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Ensure default branch exists
        $defaultBranchId = DB::table('tenant_branches')->where('tenant_id', $defaultTenantId)->value('id');
        if (! $defaultBranchId) {
            $defaultBranchId = DB::table('tenant_branches')->insertGetId([
                'tenant_id'  => $defaultTenantId,
                'store_id'   => $defaultStoreId,
                'name'       => 'Primary Branch',
                'slug'       => 'primary',
                'is_default' => 1,
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Upgrade `categories` table
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (! Schema::hasColumn('categories', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                }
                if (! Schema::hasColumn('categories', 'order')) {
                    $table->integer('order')->default(0);
                }
                if (! Schema::hasColumn('categories', 'status')) {
                    $table->enum('status', ['active', 'inactive'])->default('active');
                }
                if (! Schema::hasColumn('categories', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            DB::table('categories')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }

        // 3. Upgrade `products` table
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                }
                if (! Schema::hasColumn('products', 'brand_id')) {
                    $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
                }
                if (! Schema::hasColumn('products', 'type')) {
                    $table->enum('type', ['simple', 'variable', 'digital'])->default('simple');
                }
                if (! Schema::hasColumn('products', 'cost_price')) {
                    $table->decimal('cost_price', 12, 4)->nullable();
                }
                if (! Schema::hasColumn('products', 'currency')) {
                    $table->string('currency', 10)->default('USD');
                }
                if (! Schema::hasColumn('products', 'short_description')) {
                    $table->text('short_description')->nullable();
                }
                if (! Schema::hasColumn('products', 'status')) {
                    $table->enum('status', ['draft', 'published', 'archived'])->default('published');
                }
                if (! Schema::hasColumn('products', 'has_variants')) {
                    $table->boolean('has_variants')->default(false);
                }
                if (! Schema::hasColumn('products', 'meta_keywords')) {
                    $table->string('meta_keywords')->nullable();
                }
                if (! Schema::hasColumn('products', 'metadata')) {
                    $table->json('metadata')->nullable();
                }
                if (! Schema::hasColumn('products', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            DB::table('products')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }

        // 4. Upgrade `product_variants` table
        if (Schema::hasTable('product_variants')) {
            Schema::table('product_variants', function (Blueprint $table) {
                if (! Schema::hasColumn('product_variants', 'tenant_id')) {
                    $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                }
                if (! Schema::hasColumn('product_variants', 'compare_at_price')) {
                    $table->decimal('compare_at_price', 12, 4)->nullable();
                }
                if (! Schema::hasColumn('product_variants', 'cost_price')) {
                    $table->decimal('cost_price', 12, 4)->nullable();
                }
                if (! Schema::hasColumn('product_variants', 'dimensions')) {
                    $table->json('dimensions')->nullable();
                }
                if (! Schema::hasColumn('product_variants', 'image_url')) {
                    $table->string('image_url')->nullable();
                }
                if (! Schema::hasColumn('product_variants', 'deleted_at')) {
                    $table->softDeletes();
                }
            });

            DB::table('product_variants')->whereNull('tenant_id')->update(['tenant_id' => $defaultTenantId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep non-destructive
    }
};
