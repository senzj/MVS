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

            $default_order = config('storeconfig.default_order_type');
            $table->enum('order_type', ['walk_in', 'deliver'])
                ->default($default_order);

            $table->string('payment_type')
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

            $table->decimal('order_total', 10, 2);

            $table->foreignId('discount_preset_id')->nullable()->constrained('discount_preset')->nullOnDelete();
            $table->enum('discount_type', ['percentage', 'fixed', 'none'])->default('none');
            $table->decimal('discount_value', 10, 2)->default(0.00);

            $table->decimal('change_amount', 10, 2)->nullable();
            $table->decimal('amount_received', 10, 2)->nullable();

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
