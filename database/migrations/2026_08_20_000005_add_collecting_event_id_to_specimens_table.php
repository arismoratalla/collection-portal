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
        Schema::create('collecting_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('locality_id')
                ->nullable()
                ->constrained('localities')
                ->nullOnDelete();
            $table->string('field_number')->nullable();
            $table->date('event_date')->nullable();
            $table->date('event_date_start')->nullable();
            $table->date('event_date_end')->nullable();
            $table->string('verbatim_event_date')->nullable();
            $table->string('sampling_protocol')->nullable();
            $table->text('habitat')->nullable();
            $table->text('field_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collecting_events');
    }
};
