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
        if (! Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->unsignedBigInteger('vendor_id')->nullable()->index();

                $table->string('code', 50)->index();
                $table->string('name');
                $table->text('description')->nullable();

                // Discount type: percentage (e.g. 15%) or fixed amount (e.g. $20.00 USD)
                $table->enum('type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('value', 12, 4);

                // Conditions and caps
                $table->decimal('min_order_amount', 12, 4)->default(0.0000);
                $table->decimal('max_discount_amount', 12, 4)->nullable();

                // Usage limits
                $table->unsignedInteger('usage_limit')->nullable(); // Total global redemptions allowed
                $table->unsignedInteger('usage_limit_per_user')->default(1); // Redemptions per customer/email
                $table->unsignedInteger('times_used')->default(0);

                // Validity window
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();

                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'code']);
            });
        }

        // Ensure orders table has coupon_code column for easy indexing
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'coupon_code')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('coupon_code', 50)->nullable()->after('currency')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'coupon_code')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('coupon_code');
            });
        }

        Schema::dropIfExists('coupons');
    }
};
