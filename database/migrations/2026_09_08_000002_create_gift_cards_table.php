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
        if (!Schema::hasTable('gift_cards')) {
            Schema::create('gift_cards', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('code', 32)->unique();
                $table->decimal('initial_balance', 12, 2);
                $table->decimal('current_balance', 12, 2);
                $table->string('currency', 3)->default('USD');
                $table->string('recipient_email', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->dateTime('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gift_cards');
    }
};
