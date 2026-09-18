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
        Schema::table('specimens', function (Blueprint $table) {
            $table->unique(['collection_id', 'catalog_number']);
            $table->dropIndex(['collection_id', 'catalog_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('specimens', function (Blueprint $table) {
            $table->dropUnique(['collection_id', 'catalog_number']);
            $table->index(['collection_id', 'catalog_number']);
        });
    }
};
