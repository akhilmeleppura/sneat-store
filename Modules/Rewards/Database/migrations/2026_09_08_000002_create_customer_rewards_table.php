<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_rewards')) {
            Schema::create('customer_rewards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('customer_email')->index();
                $table->integer('current_points')->default(0);
                $table->integer('lifetime_points')->default(0);
                $table->string('tier')->default('Bronze'); // Bronze, Silver, Gold, Platinum
                $table->timestamps();

                $table->unique(['tenant_id', 'customer_email']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_rewards');
    }
};
