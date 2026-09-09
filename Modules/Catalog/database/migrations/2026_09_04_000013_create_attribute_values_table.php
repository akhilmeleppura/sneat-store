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
        if (! Schema::hasTable('attribute_values')) {
            Schema::create('attribute_values', function (Blueprint $table) {
                $table->id();
                $table->foreignId('attribute_id')->constrained('attributes')->cascadeOnDelete();
                $table->string('value');
                $table->string('label')->nullable();
                $table->string('color_code')->nullable(); // Hex color e.g. #FF0000
                $table->integer('order')->default(0);
                $table->timestamps();

                $table->unique(['attribute_id', 'value']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
