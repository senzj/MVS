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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
            ->nullable()
            ->constrained()
            ->onDelete('cascade');

            $table->foreignId('created_by')
            ->constrained('users')
            ->onDelete('cascade');

            $table->foreignId('delivered_by')
            ->nullable()
            ->constrained('employees')
            ->onDelete('cascade');

            $table->string('receipt_number')->unique();

            $table->decimal('order_total', 10, 2);

            $table->enum('order_type', ['walk_in', 'deliver'])
                ->default('deliver');

            $table->enum('payment_type', ['cash', 'gcash'])
                ->nullable();

            $table->enum('status', [
                'pending',
                'preparing',
                'in_transit',
                'delivered',
                'completed',
                'cancelled'
            ])->default('pending');

            $table->enum('payment_status', [
                'unpaid',
                'paid',
                'refunded',
            ])->default('unpaid');

            $table->string('change_amount')->nullable();
            $table->string('amount_received')->nullable();

            $table->string('proof_of_payment')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
