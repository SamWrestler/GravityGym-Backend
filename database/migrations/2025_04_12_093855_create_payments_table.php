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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('amount');
            $table->string('transaction_id')->nullable(); // Authority
            $table->string('reference_id')->nullable();   // RefID
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('gateway')->default('zarinpal');
            $table->string('description')->nullable();
            $table->json('raw_response')->nullable(); // ذخیره پاسخ کامل زرین‌پال
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
