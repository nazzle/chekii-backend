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
            $table->boolean('active')->default(true);
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignId('payment_option_id')->constrained('payment_options');

            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->nullable(); // For card payments
            $table->string('reference')->nullable(); // Check number, etc.

            $table->enum('status', ['completed', 'pending', 'failed', 'refunded'])->default('completed');
            $table->json('payment_metadata')->nullable(); // Additional payment data

            $table->timestamp('paid_at')->useCurrent();
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
