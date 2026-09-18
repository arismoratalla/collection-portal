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
        Schema::create('specimens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('occurrence_id')->unique();
            $table->string('catalog_number');
            $table->string('scientific_name')->nullable()->index();
            $table->string('basis_of_record')->nullable();
            $table->string('type_status')->nullable();
            $table->unsignedInteger('individual_count')->nullable();
            $table->timestamp('source_modified_at')->nullable();
            $table->timestamps();

            $table->index('catalog_number');
            $table->index(['collection_id', 'catalog_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specimens');
    }
};
