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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Links to users table
            $table->integer('amount'); // Positive for addition, negative for deduction
            $table->enum('transaction_type', ['purchase', 'dm']); // Type of transaction
            $table->enum('status', ['completed', 'failed']); // Status of the transaction
            $table->string('stripe_transaction_id')->nullable(); // Optional Stripe ID for purchases
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
