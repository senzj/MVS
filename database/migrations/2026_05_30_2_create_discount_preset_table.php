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
        Schema::create('discount_preset', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'Senior Citizen', 'PWD', 'Government Employee'
            $table->enum('type', ['percentage', 'fixed']); // Allows for 20% off OR $50 off
            $table->decimal('value', 10, 2); // The actual rate or amount (e.g., 20.00 or 50.00)
            $table->boolean('is_active')->default(true); // Soft-disable presets without deleting them
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_preset');
    }
};
