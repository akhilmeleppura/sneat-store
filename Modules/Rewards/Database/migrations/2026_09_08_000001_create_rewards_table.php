<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rewards')) {
            Schema::create('rewards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->string('tier')->default('standard'); // standard, bronze, silver, gold, vip
                $table->decimal('earn_rate', 8, 2)->default(1.00); // 1 point per $1 spent
                $table->decimal('redeem_rate', 8, 4)->default(0.0100); // 1 point = $0.01 (100 pts = $1)
                $table->integer('min_points_to_redeem')->default(50);
                $table->integer('max_points_per_order')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
