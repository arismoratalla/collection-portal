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
        Schema::table('collecting_events', function (Blueprint $table) {
            $table->unsignedSmallInteger('event_year')->nullable()->after('field_number');
            $table->unsignedTinyInteger('event_month')->nullable()->after('event_year');
            $table->unsignedTinyInteger('event_day')->nullable()->after('event_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collecting_events', function (Blueprint $table) {
            $table->dropColumn(['event_year', 'event_month', 'event_day']);
        });
    }
};
