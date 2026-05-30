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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('type');
            /*
                order_created
                order_updated
                order_cancelled
                refund
                manual_adjustment
                restock
            */

            $table->integer('quantity');

            // BEFORE change
            $table->integer('before_stocks');
            $table->integer('before_sold');

            // AFTER change
            $table->integer('after_stocks');
            $table->integer('after_sold');

            // Reference tracking
            $table->nullableMorphs('reference');
            /*
                reference_type
                reference_id

                Can reference:
                Order
                Refund
                ProductAdjustment
            */

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
