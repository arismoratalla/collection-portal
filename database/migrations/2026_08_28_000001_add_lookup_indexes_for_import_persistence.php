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
        Schema::table('geographies', function (Blueprint $table) {
            $table->index(['parent_id', 'geography_type', 'name', 'iso_code'], 'geographies_lookup_index');
        });

        Schema::table('taxa', function (Blueprint $table) {
            $table->index(['scientific_name', 'authorship', 'rank'], 'taxa_identity_index');
        });

        Schema::table('collecting_events', function (Blueprint $table) {
            $table->index(
                [
                    'locality_id',
                    'field_number',
                    'event_year',
                    'event_month',
                    'event_day',
                    'verbatim_event_date',
                    'sampling_protocol',
                ],
                'collecting_events_identity_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collecting_events', function (Blueprint $table) {
            $table->dropIndex('collecting_events_identity_index');
        });

        Schema::table('taxa', function (Blueprint $table) {
            $table->dropIndex('taxa_identity_index');
        });

        Schema::table('geographies', function (Blueprint $table) {
            $table->dropIndex('geographies_lookup_index');
        });
    }
};
