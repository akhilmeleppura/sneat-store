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
        if (!Schema::hasTable('tax_rates')) {
            Schema::create('tax_rates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->string('country_code', 2)->index(); // US, CA, GB, DE, FR, IN, etc.
                $table->string('state_code', 10)->nullable()->index();
                $table->string('tax_name', 100)->default('VAT/Sales Tax');
                $table->decimal('rate_percentage', 5, 2)->default(0.00);
                $table->boolean('is_compound')->default(false);
                $table->boolean('is_b2b_exempt')->default(true); // Exemption for B2B/wholesale VAT
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
