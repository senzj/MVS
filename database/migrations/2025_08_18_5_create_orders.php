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
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // staff who encoded
            $table->foreignId('delivery_id')->nullable()->constrained('users')->onDelete('set null'); // delivery boy
            $table->enum('payment_type', ['cash', 'gcash'])->nullable();
            $table->enum('status', ['pending', 'paid', 'delivered', 'completed', 'cancelled'])->default('pending');
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
