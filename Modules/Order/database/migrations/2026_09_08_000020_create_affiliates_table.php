<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('affiliates')) {
            Schema::create('affiliates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('affiliate_code', 50)->unique()->index();
                $table->decimal('commission_rate', 5, 2)->default(10.00); // 10%
                $table->decimal('total_earnings', 12, 4)->default(0.0000);
                $table->decimal('pending_earnings', 12, 4)->default(0.0000);
                $table->decimal('paid_earnings', 12, 4)->default(0.0000);
                $table->enum('status', ['active', 'paused', 'rejected'])->default('active')->index();
                $table->string('payout_method', 50)->default('paypal');
                $table->string('payout_account')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates');
    }
};
