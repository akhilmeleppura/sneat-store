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
        if (! Schema::hasTable('stores')) {
            Schema::create('stores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->index();
                $table->string('domain')->nullable()->index();
                $table->boolean('is_default')->default(false);
                $table->enum('status', ['active', 'inactive'])->default('active')->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'slug']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
