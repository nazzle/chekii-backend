<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');

            // Polymorphic relation
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            // Index for faster lookups
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');

            // Foreign key constraint to roles table
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            // Composite primary key (unique per model + role)
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_has_roles');
    }
};
