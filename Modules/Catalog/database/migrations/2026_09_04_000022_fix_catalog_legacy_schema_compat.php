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
        // 1. Make products.category_id nullable
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'category_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable()->change();
            });
        }

        // 2. Add attribute_id and make product_attribute_id nullable in attribute_values
        if (Schema::hasTable('attribute_values')) {
            Schema::table('attribute_values', function (Blueprint $table) {
                if (Schema::hasColumn('attribute_values', 'product_attribute_id')) {
                    $table->unsignedBigInteger('product_attribute_id')->nullable()->change();
                }
                if (! Schema::hasColumn('attribute_values', 'attribute_id')) {
                    $table->foreignId('attribute_id')->nullable()->after('id')->constrained('attributes')->cascadeOnDelete();
                }
                if (! Schema::hasColumn('attribute_values', 'label')) {
                    $table->string('label')->nullable()->after('value');
                }
                if (! Schema::hasColumn('attribute_values', 'order')) {
                    $table->integer('order')->default(0);
                }
            });
        }

        // 3. Make legacy columns in product_variants nullable
        if (Schema::hasTable('product_variants')) {
            Schema::table('product_variants', function (Blueprint $table) {
                if (Schema::hasColumn('product_variants', 'attribute_name')) {
                    $table->string('attribute_name')->nullable()->change();
                }
                if (Schema::hasColumn('product_variants', 'attribute_value')) {
                    $table->string('attribute_value')->nullable()->change();
                }
                if (Schema::hasColumn('product_variants', 'qty')) {
                    $table->integer('qty')->default(0)->nullable()->change();
                }
                if (Schema::hasColumn('product_variants', 'image')) {
                    $table->string('image')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive
    }
};
