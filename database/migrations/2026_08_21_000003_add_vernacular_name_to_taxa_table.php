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
        Schema::table('taxa', function (Blueprint $table) {
            $table->string('vernacular_name')->nullable()->after('infraspecific_epithet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxa', function (Blueprint $table) {
            $table->dropColumn('vernacular_name');
        });
    }
};
