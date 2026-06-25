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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->string('lang')->default('en');
            $table->boolean('change_password')->default(false);
            $table->date('birth_date');  // used for password reset verification
            $table->unsignedInteger('pin_code'); // used for password reset verification
            $table->enum('theme', ['system', 'light', 'dark'])->default('system');
            $table->timestamps();
        });

        Schema::create('remember_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->index();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('token');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('last_used_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('remember_devices');
        Schema::dropIfExists('sessions');
    }
};
