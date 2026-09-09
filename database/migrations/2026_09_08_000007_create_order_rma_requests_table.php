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
        if (!Schema::hasTable('order_rma_requests')) {
            Schema::create('order_rma_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('order_id')->index();
                $table->unsignedBigInteger('order_item_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('rma_number', 50)->unique();
                $table->string('reason', 255);
                $table->string('condition', 100)->default('unopened'); // unopened, opened, damaged, defective
                $table->string('resolution_type', 50)->default('refund'); // refund, exchange, store_credit
                $table->string('status', 50)->default('pending'); // pending, approved, label_issued, received, inspected, resolved, rejected
                $table->string('return_tracking_number', 100)->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_rma_requests');
    }
};
