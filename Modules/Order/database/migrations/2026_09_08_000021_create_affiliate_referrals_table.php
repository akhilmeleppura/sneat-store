<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('affiliate_referrals')) {
            Schema::create('affiliate_referrals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('customer_email')->index();
                $table->decimal('order_amount', 12, 4);
                $table->decimal('commission_rate', 5, 2);
                $table->decimal('commission_amount', 12, 4);
                $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending')->index();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_referrals');
    }
};
