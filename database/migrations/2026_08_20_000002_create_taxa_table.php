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
        Schema::create('taxa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('taxa')
                ->nullOnDelete();
            $table->string('scientific_name');
            $table->string('canonical_name')->nullable()->index();
            $table->string('authorship')->nullable();
            $table->string('rank')->nullable();
            $table->string('kingdom')->nullable();
            $table->string('phylum')->nullable();
            $table->string('class_name')->nullable();
            $table->string('order_name')->nullable();
            $table->string('family')->nullable();
            $table->string('genus')->nullable();
            $table->string('specific_epithet')->nullable();
            $table->string('infraspecific_epithet')->nullable();
            $table->string('source')->nullable();
            $table->string('source_identifier')->nullable();
            $table->boolean('is_accepted')->default(true);
            $table->timestamps();

            $table->index('scientific_name');
            $table->unique(['source', 'source_identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxa');
    }
};
