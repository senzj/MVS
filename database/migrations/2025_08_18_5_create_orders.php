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
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade'); // staff who encoded
            $table->foreignId('delivered_by')->nullable()->constrained('employees')->onDelete('cascade'); // delivery boy
            $table->decimal('order_total', 10, 2);
            $table->enum('payment_type', ['cash', 'gcash'])->nullable();
            $table->enum('status', ['pending', 'in_transit', 'delivered', 'completed', 'cancelled'])->default('pending');
            $table->boolean('is_paid')->default(false);
            $table->string('receipt_number')->unique();
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
