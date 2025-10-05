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
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
            $table->string('company_name');
            $table->string('company_logo');
            $table->string('address');
            $table->string('website');
            $table->string('email');
            $table->string('phone');
            $table->string('return_policy');
            $table->string('currency_code');
            $table->string('currency_symbol');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
