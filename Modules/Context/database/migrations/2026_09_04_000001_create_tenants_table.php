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
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique()->index();
                $table->string('domain')->nullable()->unique()->index();
                $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->index();
                
                // Dedicated DB support for hybrid / compliance isolation
                $table->string('db_connection')->nullable();
                $table->string('db_host')->nullable();
                $table->string('db_port')->nullable();
                $table->string('db_database')->nullable();
                $table->string('db_username')->nullable();
                $table->text('db_password')->nullable();

                // Localization & Region settings per tenant
                $table->string('timezone')->default('UTC');
                $table->string('currency', 10)->default('USD');
                $table->string('locale', 10)->default('en');

                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
