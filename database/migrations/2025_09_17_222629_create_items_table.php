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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
            $table->string('barcode')->nullable();
            $table->string('item_code')->unique();
            $table->text('description')->nullable();
            $table->text('item_image')->nullable();
            $table->decimal('buying_price', 10, 2);
            $table->decimal('selling_price', 10, 2);
            $table->enum('gender', ['male', 'female', 'unisex'])->nullable();
            $table->unsignedBigInteger('category_id');
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreignId('type_id')->constrained('item_types')->onDelete('cascade');
            $table->foreignId('age_id')->constrained('age_groups')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
