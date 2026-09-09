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
        if (! Schema::hasTable('product_reviews')) {
            Schema::create('product_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->unsignedBigInteger('vendor_id')->nullable()->index();

                $table->unsignedTinyInteger('rating')->default(5); // 1 to 5 stars
                $table->string('title')->nullable();
                $table->text('comment');

                $table->boolean('is_verified_buyer')->default(false)->index();
                $table->boolean('is_approved')->default(true)->index();

                $table->text('admin_reply')->nullable();
                $table->timestamp('replied_at')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'product_id', 'is_approved']);
                $table->index(['tenant_id', 'user_id']);
            });
        }

        // Add rating aggregates cache to products table for instant querying
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'rating_cache')) {
                    $table->decimal('rating_cache', 3, 2)->default(0.00)->after('price')->index();
                }
                if (! Schema::hasColumn('products', 'rating_count')) {
                    $table->unsignedInteger('rating_count')->default(0)->after('rating_cache')->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'rating_cache')) {
                    $table->dropColumn('rating_cache');
                }
                if (Schema::hasColumn('products', 'rating_count')) {
                    $table->dropColumn('rating_count');
                }
            });
        }

        Schema::dropIfExists('product_reviews');
    }
};
