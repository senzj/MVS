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
        Schema::create('item_restocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restock_id')
                ->constrained('product_restocks')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->integer('quantity');
            $table->decimal('unit_cost', 10, 2); // What you paid per unit/kg
            $table->decimal('total_cost', 10, 2); // quantity * unit_cost
            $table->string('unit_type')
                ->default('pcs'); // pcs, kg, g, box, etc.

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_restocks');
    }
};
