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
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('id')->on('items');
            $table->unsignedBigInteger('from_location');
            $table->foreign('from_location')->references('id')->on('locations');
            $table->unsignedBigInteger('to_location');
            $table->foreign('to_location')->references('id')->on('locations');
            $table->integer('quantity')->default(0);
            $table->enum('movement_type', ['transfer', 'sale', 'purchase', 'adjustment']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
