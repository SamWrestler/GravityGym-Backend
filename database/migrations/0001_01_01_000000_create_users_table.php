<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('phone_number')->unique();
            $table->string('national_id')->nullable()->unique();
            $table->date('birthdate')->nullable();  // Add birthdate field
            $table->enum('role', ['athlete', 'instructor', 'admin' , 'superUser'])->default('athlete'); // Default role as 'athlete'
            $table->enum('gender', ['male', 'female'])->nullable(); // Default gender as 'male'
            $table->integer('height')->nullable();
            $table->integer('weight')->nullable();
            $table->enum('insurance', ['yes', 'no'])->default('no');
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
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
        Schema::dropIfExists('sessions');
    }
};
