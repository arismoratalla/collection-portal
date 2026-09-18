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
        Schema::create('preparations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specimen_id')
                ->constrained('specimens')
                ->cascadeOnDelete();
            $table->string('preparation_type');
            $table->unsignedInteger('count')->nullable();
            $table->string('storage_medium')->nullable();
            $table->string('storage_location')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('preparation_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preparations');
    }
};
