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
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            // Category relationship
            $table->foreignId('category_id')
                  ->constrained()
                  ->restrictOnDelete();

            // Basic info
            $table->string('name');
            $table->string('slug')->unique();

            // Stock / tracking
            $table->string('sku')->unique();
            $table->string('barcode')->unique()->nullable();

            // Visuals
            $table->string('image')->nullable();

            // Pricing
            $table->decimal('cost_price', 10, 2);
            $table->decimal('selling_price', 10, 2);

            // Measurement
            $table->string('unit')->default('pcs');

            // POS visibility
            $table->boolean('is_active')->default(true);

            // Timestamps and soft delete
            $table->timestamps();
            $table->softDeletes();

            // Performance index
            $table->index(['category_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
