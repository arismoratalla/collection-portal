<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collecting_event_collector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collecting_event_id')
                ->constrained('collecting_events')
                ->cascadeOnDelete();
            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete();
            $table->unsignedInteger('sequence')->nullable();
            $table->timestamps();

            $table->unique(['collecting_event_id', 'agent_id']);
            $table->index('sequence');
        });

        $rows = DB::table('specimen_collector as sc')
            ->join('specimens as s', 's.id', '=', 'sc.specimen_id')
            ->whereNotNull('s.collecting_event_id')
            ->groupBy('s.collecting_event_id', 'sc.agent_id')
            ->select([
                's.collecting_event_id',
                'sc.agent_id',
            ])
            ->selectRaw('MIN(sc.sequence) as sequence')
            ->get()
            ->map(fn ($row) => [
                'collecting_event_id' => $row->collecting_event_id,
                'agent_id' => $row->agent_id,
                'sequence' => $row->sequence,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if ($rows !== []) {
            DB::table('collecting_event_collector')->insert($rows);
        }

        Schema::dropIfExists('specimen_collector');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('specimen_collector', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specimen_id')
                ->constrained('specimens')
                ->cascadeOnDelete();
            $table->foreignId('agent_id')
                ->constrained('agents')
                ->cascadeOnDelete();
            $table->unsignedInteger('sequence')->nullable();
            $table->timestamps();

            $table->unique(['specimen_id', 'agent_id']);
            $table->index('sequence');
        });

        Schema::dropIfExists('collecting_event_collector');
    }
};
