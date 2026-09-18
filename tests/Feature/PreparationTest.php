<?php

namespace Tests\Feature;

use App\Models\Preparation;
use App\Models\Specimen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_specimen_has_many_preparations(): void
    {
        $specimen = Specimen::factory()->create();
        Preparation::factory()->count(2)->create([
            'specimen_id' => $specimen->id,
        ]);

        $this->assertCount(2, $specimen->preparations);
    }

    public function test_preparation_belongs_to_specimen(): void
    {
        $preparation = Preparation::factory()->create();

        $this->assertInstanceOf(Specimen::class, $preparation->specimen);
    }

    public function test_preparation_factory_can_create_a_preparation(): void
    {
        $preparation = Preparation::factory()->create([
            'preparation_type' => 'tissue',
            'count' => 3,
        ]);

        $this->assertDatabaseHas('preparations', [
            'id' => $preparation->id,
            'preparation_type' => 'tissue',
            'count' => 3,
        ]);
    }

    public function test_preparation_can_preserve_raw_source_values(): void
    {
        $preparation = Preparation::factory()->create([
            'source_value' => '3 EtOH70',
        ]);

        $this->assertSame('3 EtOH70', $preparation->source_value);
        $this->assertDatabaseHas('preparations', [
            'id' => $preparation->id,
            'source_value' => '3 EtOH70',
        ]);
    }
}
