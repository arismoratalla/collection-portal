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
        Schema::table('determinations', function (Blueprint $table) {
            $table->dropColumn('type_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('determinations', function (Blueprint $table) {
            $table->string('type_status')->nullable()->after('determined_at');
        });
    }
};
