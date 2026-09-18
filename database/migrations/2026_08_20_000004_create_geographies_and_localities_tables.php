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
        Schema::create('geographies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('geographies')
                ->nullOnDelete();
            $table->string('name');
            $table->string('geography_type');
            $table->string('iso_code')->nullable();
            $table->timestamps();
        });

        Schema::create('localities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geography_id')
                ->nullable()
                ->constrained('geographies')
                ->nullOnDelete();
            $table->text('verbatim_locality')->nullable();
            $table->text('locality')->nullable();
            $table->decimal('decimal_latitude', 10, 7)->nullable();
            $table->decimal('decimal_longitude', 10, 7)->nullable();
            $table->decimal('coordinate_uncertainty_meters', 12, 2)->nullable();
            $table->string('geodetic_datum')->nullable();
            $table->text('georeference_sources')->nullable();
            $table->text('georeference_remarks')->nullable();
            $table->boolean('coordinates_public')->default(true);
            $table->boolean('sensitive')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localities');
        Schema::dropIfExists('geographies');
    }
};
