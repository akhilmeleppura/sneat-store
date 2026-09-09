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
        if (Schema::hasTable('product_variant_attributes') && Schema::hasColumn('product_variant_attributes', 'attribute_id')) {
            Schema::table('product_variant_attributes', function (Blueprint $table) {
                $table->dropForeign(['attribute_id']);
                $table->unsignedBigInteger('attribute_id')->nullable()->change();
                $table->foreign('attribute_id')->references('id')->on('attributes')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
