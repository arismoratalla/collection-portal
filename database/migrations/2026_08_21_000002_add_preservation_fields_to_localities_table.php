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
        Schema::table('localities', function (Blueprint $table) {
            $table->string('water_body')->nullable()->after('georeference_remarks');
            $table->string('verbatim_depth')->nullable()->after('water_body');
            $table->text('footprint_wkt')->nullable()->after('verbatim_depth');
            $table->string('georeferenced_by')->nullable()->after('footprint_wkt');
            $table->date('georeferenced_date')->nullable()->after('georeferenced_by');
            $table->string('georeference_protocol')->nullable()->after('georeferenced_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('localities', function (Blueprint $table) {
            $table->dropColumn([
                'water_body',
                'verbatim_depth',
                'footprint_wkt',
                'georeferenced_by',
                'georeferenced_date',
                'georeference_protocol',
            ]);
        });
    }
};
