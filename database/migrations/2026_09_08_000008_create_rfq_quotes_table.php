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
        if (!Schema::hasTable('rfq_quotes')) {
            Schema::create('rfq_quotes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('quote_number', 50)->unique();
                $table->string('company_name', 255);
                $table->string('contact_name', 255);
                $table->string('contact_email', 255);
                $table->string('contact_phone', 50)->nullable();
                $table->string('tax_id', 50)->nullable();
                $table->json('items_payload'); // Array of products, SKUs, and target quantities
                $table->decimal('quoted_total', 12, 2)->nullable();
                $table->string('status', 30)->default('pending'); // pending, quoted, accepted, declined, expired
                $table->dateTime('valid_until')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfq_quotes');
    }
};
