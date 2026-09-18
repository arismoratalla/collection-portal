<?php

namespace Tests\Feature;

use App\Models\Determination;
use App\Models\Specimen;
use App\Models\Taxon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeterminationTest extends TestCase
{
    use RefreshDatabase;

    public function test_specimen_can_have_multiple_determinations_and_current_identification(): void
    {
        $specimen = Specimen::factory()->create();
        $firstTaxon = Taxon::factory()->create(['scientific_name' => 'Animalia']);
        $currentTaxon = Taxon::factory()->create(['scientific_name' => 'Chordata']);

        $first = Determination::factory()->create([
            'specimen_id' => $specimen->id,
            'taxon_id' => $firstTaxon->id,
            'is_current' => false,
        ]);

        $current = Determination::factory()->create([
            'specimen_id' => $specimen->id,
            'taxon_id' => $currentTaxon->id,
            'is_current' => true,
        ]);

        $this->assertCount(2, $specimen->determinations);
        $this->assertTrue($specimen->currentDetermination->is($current));
        $this->assertTrue($specimen->currentDetermination->taxon->is($currentTaxon));
        $this->assertTrue($specimen->determinations->contains($first));
    }

    public function test_determination_factory_can_create_a_determination(): void
    {
        $determination = Determination::factory()->create();

        $this->assertDatabaseHas('determinations', [
            'id' => $determination->id,
            'specimen_id' => $determination->specimen_id,
            'taxon_id' => $determination->taxon_id,
        ]);
    }
}
