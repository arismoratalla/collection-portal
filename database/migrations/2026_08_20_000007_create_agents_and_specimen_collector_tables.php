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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name');
            $table->string('organization')->nullable();
            $table->string('orcid')->nullable();
            $table->timestamps();
        });

        Schema::create('specimen_collector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specimen_id')
                ->constrained('specimens')
                ->cascadeOnDelete();
            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete();
            $table->unsignedInteger('sequence')->nullable();
            $table->timestamps();

            $table->unique(['specimen_id', 'agent_id']);
            $table->index('sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specimen_collector');
        Schema::dropIfExists('agents');
    }
};
