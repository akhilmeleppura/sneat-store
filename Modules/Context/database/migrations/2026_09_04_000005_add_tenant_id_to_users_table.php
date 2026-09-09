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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tenant_id')) {
                $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'store_id')) {
                $table->foreignId('store_id')->nullable()->after('tenant_id')->constrained('stores')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'tenant_branch_id')) {
                $table->foreignId('tenant_branch_id')->nullable()->after('store_id')->constrained('tenant_branches')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'tenant_branch_id')) {
                $table->dropForeign(['tenant_branch_id']);
                $table->dropColumn('tenant_branch_id');
            }
            if (Schema::hasColumn('users', 'store_id')) {
                $table->dropForeign(['store_id']);
                $table->dropColumn('store_id');
            }
            if (Schema::hasColumn('users', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });
    }
};
