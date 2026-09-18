<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Specimen;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SpecimenTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_has_many_specimens(): void
    {
        $collection = Collection::factory()->create();
        Specimen::factory()->count(3)->create([
            'collection_id' => $collection->id,
        ]);

        $this->assertCount(3, $collection->specimens);
    }

    public function test_specimen_belongs_to_collection(): void
    {
        $specimen = Specimen::factory()->create();

        $this->assertInstanceOf(Collection::class, $specimen->collection);
    }

    public function test_specimen_factory_can_create_a_specimen(): void
    {
        $specimen = Specimen::factory()->create();

        $this->assertDatabaseHas('specimens', [
            'id' => $specimen->id,
            'occurrence_id' => $specimen->occurrence_id,
        ]);

        $this->assertInstanceOf(Carbon::class, $specimen->source_modified_at);
        $this->assertIsInt($specimen->individual_count ?? 0);
    }

    public function test_collection_cannot_be_deleted_when_specimens_reference_it(): void
    {
        $collection = Collection::factory()->create();
        Specimen::factory()->create([
            'collection_id' => $collection->id,
        ]);

        $this->expectException(QueryException::class);

        $collection->delete();
    }

    public function test_catalog_number_is_unique_within_a_collection_but_can_repeat_across_collections(): void
    {
        $fish = Collection::factory()->create([
            'name' => 'Fish',
            'slug' => 'fish',
        ]);
        $birds = Collection::factory()->create([
            'name' => 'Birds',
            'slug' => 'birds',
        ]);

        Specimen::factory()->create([
            'collection_id' => $fish->id,
            'catalog_number' => '12345',
            'occurrence_id' => 'fish-occurrence-1',
        ]);

        Specimen::factory()->create([
            'collection_id' => $birds->id,
            'catalog_number' => '12345',
            'occurrence_id' => 'birds-occurrence-1',
        ]);

        $this->expectException(QueryException::class);

        Specimen::factory()->create([
            'collection_id' => $fish->id,
            'catalog_number' => '12345',
            'occurrence_id' => 'fish-occurrence-2',
        ]);
    }

    public function test_occurrence_id_is_globally_unique(): void
    {
        $fish = Collection::factory()->create([
            'name' => 'Fish',
            'slug' => 'fish',
        ]);
        $birds = Collection::factory()->create([
            'name' => 'Birds',
            'slug' => 'birds',
        ]);

        Specimen::factory()->create([
            'collection_id' => $fish->id,
            'catalog_number' => 'F-1',
            'occurrence_id' => 'duplicate-occurrence-id',
        ]);

        $this->expectException(QueryException::class);

        Specimen::factory()->create([
            'collection_id' => $birds->id,
            'catalog_number' => 'B-1',
            'occurrence_id' => 'duplicate-occurrence-id',
        ]);
    }

    public function test_specimen_relationship_fields_are_cast_and_indexed_inputs_store(): void
    {
        $specimen = Specimen::factory()->create([
            'individual_count' => 7,
            'source_modified_at' => '2026-08-20 12:34:56',
        ]);

        $this->assertSame(7, $specimen->individual_count);
        $this->assertNotNull($specimen->source_modified_at);
        $this->assertSame('2026-08-20 12:34:56', $specimen->source_modified_at->format('Y-m-d H:i:s'));
    }

    public function test_source_modified_at_preserves_the_supplied_value_without_timezone_drift(): void
    {
        $specimen = Specimen::factory()->create([
            'source_modified_at' => '2026-01-23 11:35:44',
        ]);

        $this->assertSame('2026-01-23 11:35:44', $specimen->source_modified_at->format('Y-m-d H:i:s'));
    }
}
