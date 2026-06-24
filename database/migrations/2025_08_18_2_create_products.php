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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('color')->nullable()->unique();
            $table->integer('stocks');
            $table->integer('sold')->default(0); // Track how many items have been sold
            $table->boolean('is_in_stock')->default(true);
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2)->default(0.00); // weighted average cost
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
