<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add customer contact to carts table if missing
        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {
                if (! Schema::hasColumn('carts', 'customer_email')) {
                    $table->string('customer_email')->nullable()->after('coupon_code');
                }
                if (! Schema::hasColumn('carts', 'customer_phone')) {
                    $table->string('customer_phone', 30)->nullable()->after('customer_email');
                }
            });
        }

        // 2. Create abandoned cart recoveries table
        if (! Schema::hasTable('abandoned_cart_recoveries')) {
            Schema::create('abandoned_cart_recoveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('customer_email')->index();
                $table->string('customer_phone', 30)->nullable();
                $table->decimal('cart_subtotal', 12, 4)->default(0.0000);
                $table->string('currency', 10)->default('USD');
                $table->string('recovery_token', 64)->unique()->index();
                $table->enum('status', ['pending', 'first_reminder_sent', 'second_reminder_sent', 'recovered', 'expired'])->default('pending')->index();
                $table->string('recovery_discount_code', 50)->nullable();
                $table->integer('items_count')->default(1);
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('recovered_at')->nullable();
                $table->foreignId('recovered_order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('abandoned_cart_recoveries');

        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table) {
                if (Schema::hasColumn('carts', 'customer_phone')) {
                    $table->dropColumn('customer_phone');
                }
                if (Schema::hasColumn('carts', 'customer_email')) {
                    $table->dropColumn('customer_email');
                }
            });
        }
    }
};
