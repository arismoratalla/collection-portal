<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Services\Imports\CollectionImportMappingRegistry;
use App\Services\Imports\DarwinCoreOccurrenceMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionImportMappingRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_classifies_known_fields(): void
    {
        $registry = app(CollectionImportMappingRegistry::class);

        $this->assertSame('direct', $registry->classify('id'));
        $this->assertSame('domain', $registry->classify('scientificName'));
        $this->assertSame('metadata', $registry->classify('license'));
        $this->assertSame('unmapped', $registry->classify('unknownField'));
    }

    public function test_mapper_returns_nested_domain_data_and_preserves_catalog_number_as_string(): void
    {
        $collection = Collection::factory()->create([
            'slug' => 'fish',
        ]);

        $mapped = app(DarwinCoreOccurrenceMapper::class)->map([
            'id' => 'occ-1',
            'catalogNumber' => '000123',
            'modified' => '2026-08-20',
            'scientificName' => 'Gadus morhua',
            'country' => 'United States',
            'fieldNumber' => 'FN-1',
            'preparations' => 'alcohol',
            'license' => 'CC-BY',
        ], $collection);

        $this->assertSame('000123', $mapped['specimen']['catalog_number']);
        $this->assertSame('occ-1', $mapped['specimen']['occurrence_id']);
        $this->assertSame('2026-08-20', $mapped['specimen']['source_modified_at']);
        $this->assertSame('Gadus morhua', $mapped['taxon']['scientific_name']);
        $this->assertSame('United States', $mapped['locality']['country']);
        $this->assertSame('FN-1', $mapped['collecting_event']['field_number']);
        $this->assertSame('alcohol', $mapped['preparations']['raw']);
        $this->assertSame('CC-BY', $mapped['metadata']['license']);
        $this->assertNull($mapped['locality']['state_province']);
    }
}
