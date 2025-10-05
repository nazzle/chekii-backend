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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique(); // SL-001, SL-002
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Cashier/employee
            $table->foreignId('location_id')->constrained('locations'); // If multiple locations
            $table->foreignId('payment_option_id')->constrained('payment_options');

            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2);
            $table->decimal('change_amount', 10, 2)->default(0);

            $table->enum('status', ['completed', 'pending', 'cancelled', 'refunded'])->default('completed');
            $table->enum('sale_type', ['walk-in', 'online', 'phone', 'delivery'])->default('walk-in');

            $table->text('notes')->nullable();
            $table->timestamp('sale_date')->useCurrent();

            // For returns/refunds
            $table->foreignId('original_sale_id')->nullable()->constrained('sales');
            $table->boolean('is_refund')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
