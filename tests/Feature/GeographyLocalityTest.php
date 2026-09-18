<?php

namespace Tests\Feature;

use App\Models\Geography;
use App\Models\Locality;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyLocalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_geography_supports_parent_and_children_relationships(): void
    {
        $parent = Geography::factory()->create([
            'name' => 'North America',
            'geography_type' => 'continent',
        ]);

        $child = Geography::factory()->create([
            'parent_id' => $parent->id,
            'name' => 'United States',
            'geography_type' => 'country',
        ]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($parent->children->contains($child));
    }

    public function test_locality_belongs_to_geography_and_stores_coordinates(): void
    {
        $geography = Geography::factory()->create();

        $locality = Locality::factory()->create([
            'geography_id' => $geography->id,
            'decimal_latitude' => 35.1234567,
            'decimal_longitude' => -77.7654321,
            'coordinate_uncertainty_meters' => 25.5,
            'geodetic_datum' => 'WGS84',
            'water_body' => 'Pamlico Sound',
            'verbatim_depth' => '3 m',
            'footprint_wkt' => 'POINT(-77.7654321 35.1234567)',
            'georeferenced_by' => 'Jane Doe',
            'georeferenced_date' => '2026-08-20',
            'georeference_protocol' => 'Gazetteer lookup',
        ]);

        $this->assertTrue($locality->geography->is($geography));
        $this->assertSame('35.1234567', (string) $locality->decimal_latitude);
        $this->assertSame('-77.7654321', (string) $locality->decimal_longitude);
        $this->assertSame('25.50', number_format((float) $locality->coordinate_uncertainty_meters, 2, '.', ''));
        $this->assertSame('Pamlico Sound', $locality->water_body);
        $this->assertSame('3 m', $locality->verbatim_depth);
        $this->assertSame('POINT(-77.7654321 35.1234567)', $locality->footprint_wkt);
        $this->assertSame('Jane Doe', $locality->georeferenced_by);
        $this->assertSame('2026-08-20', $locality->georeferenced_date->format('Y-m-d'));
        $this->assertSame('Gazetteer lookup', $locality->georeference_protocol);
    }

    public function test_locality_serialization_hides_coordinates_when_not_public_or_sensitive(): void
    {
        $privateLocality = Locality::factory()->create([
            'coordinates_public' => false,
            'sensitive' => false,
        ]);

        $sensitiveLocality = Locality::factory()->create([
            'coordinates_public' => true,
            'sensitive' => true,
        ]);

        $publicData = Locality::factory()->create([
            'coordinates_public' => true,
            'sensitive' => false,
            'decimal_latitude' => 10.1234567,
            'decimal_longitude' => 20.7654321,
        ])->toArray();

        $this->assertArrayNotHasKey('decimal_latitude', $privateLocality->toArray());
        $this->assertArrayNotHasKey('decimal_longitude', $privateLocality->toArray());
        $this->assertArrayNotHasKey('decimal_latitude', $sensitiveLocality->toArray());
        $this->assertArrayNotHasKey('decimal_longitude', $sensitiveLocality->toArray());
        $this->assertArrayHasKey('decimal_latitude', $publicData);
        $this->assertArrayHasKey('decimal_longitude', $publicData);
    }
}
