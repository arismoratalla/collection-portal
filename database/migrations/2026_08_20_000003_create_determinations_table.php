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
        Schema::create('determinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specimen_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('taxon_id')
                ->constrained('taxa')
                ->restrictOnDelete();
            $table->string('determiner_name')->nullable();
            $table->date('determined_at')->nullable();
            $table->string('type_status')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index('specimen_id');
            $table->index('taxon_id');
            $table->index('is_current');
            $table->index(['specimen_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('determinations');
    }
};
