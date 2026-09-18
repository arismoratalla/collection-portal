<?php

namespace Tests\Feature;

use App\Models\CollectingEvent;
use App\Models\Collection;
use App\Models\Determination;
use App\Models\Geography;
use App\Models\Locality;
use App\Models\Preparation;
use App\Models\Specimen;
use App\Models\Taxon;
use App\Services\CollectionImports\CollectionImportFileLocator;
use App\Services\Imports\DarwinCoreOccurrenceMapper;
use App\Services\Imports\DarwinCoreOccurrencePersister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DarwinCoreOccurrencePersisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_persister_creates_the_core_related_records_for_a_mapped_occurrence(): void
    {
        $collection = Collection::factory()->create(['slug' => 'fish']);
        $mapped = $this->mappedOccurrence($collection, [
            'id' => 'occ-1',
            'catalogNumber' => '000123',
            'modified' => '2026-01-23 11:35:44',
            'scientificName' => 'Gadus morhua',
            'scientificNameAuthorship' => 'Linnaeus, 1758',
            'taxonRank' => 'species',
            'vernacularName' => 'Cod',
            'country' => 'United States',
            'stateProvince' => 'North Carolina',
            'county' => 'Dare County',
            'continent' => 'North America',
            'locality' => 'Cape Hatteras',
            'verbatimLocality' => 'Cape Hatteras, Dare County',
            'waterBody' => 'Pamlico Sound',
            'verbatimDepth' => '3 m',
            'decimalLatitude' => '35.1234567',
            'decimalLongitude' => '-75.1234567',
            'geodeticDatum' => 'WGS84',
            'coordinateUncertaintyInMeters' => '25.5',
            'footprintWKT' => 'POINT(-75.1234567 35.1234567)',
            'georeferencedBy' => 'Jane Doe',
            'georeferencedDate' => '2026-08-20',
            'georeferenceProtocol' => 'Gazetteer lookup',
            'georeferenceSources' => 'NGA gazetteer',
            'georeferenceRemarks' => 'Verified by hand',
            'fieldNumber' => 'FN-1',
            'year' => '1941',
            'month' => '5',
            'day' => '17',
            'verbatimEventDate' => '17 May 1941',
            'samplingProtocol' => 'Seine net',
            'identifiedBy' => 'A. Researcher',
            'preparations' => '3 EtOH70',
        ]);

        $result = app(DarwinCoreOccurrencePersister::class)->persist($mapped, $collection);

        $specimen = Specimen::query()->where('occurrence_id', 'occ-1')->firstOrFail();
        $collectingEvent = $specimen->collectingEvent;
        $locality = $collectingEvent->locality;
        $taxon = Taxon::query()->firstOrFail();
        $determination = Determination::query()->firstOrFail();
        $preparation = Preparation::query()->firstOrFail();

        $this->assertSame($collection->id, $specimen->collection_id);
        $this->assertSame('000123', $specimen->catalog_number);
        $this->assertSame('2026-01-23 11:35:44', (string) $specimen->getRawOriginal('source_modified_at'));
        $this->assertSame(1, Specimen::count());

        $this->assertSame(1, CollectingEvent::count());
        $this->assertSame(1941, $collectingEvent->event_year);
        $this->assertSame(5, $collectingEvent->event_month);
        $this->assertSame(17, $collectingEvent->event_day);
        $this->assertSame('1941-05-17 00:00:00', (string) $collectingEvent->getRawOriginal('event_date'));
        $this->assertSame('FN-1', $collectingEvent->field_number);

        $this->assertSame(1, Locality::count());
        $this->assertSame('Cape Hatteras', $locality->locality);
        $this->assertSame('Pamlico Sound', $locality->water_body);
        $this->assertSame('3 m', $locality->verbatim_depth);
        $this->assertSame('POINT(-75.1234567 35.1234567)', $locality->footprint_wkt);
        $this->assertSame('Jane Doe', $locality->georeferenced_by);
        $this->assertDatabaseHas('localities', [
            'id' => $locality->id,
            'georeferenced_date' => '2026-08-20 00:00:00',
        ]);
        $this->assertSame('Gazetteer lookup', $locality->georeference_protocol);

        $this->assertSame(4, Geography::count());
        $this->assertSame('county', $locality->geography->geography_type);
        $this->assertSame('Dare County', $locality->geography->name);
        $this->assertSame('state', $locality->geography->parent->geography_type);
        $this->assertSame('North Carolina', $locality->geography->parent->name);

        $this->assertSame('Gadus morhua', $taxon->scientific_name);
        $this->assertSame('Cod', $taxon->vernacular_name);
        $this->assertSame(1, Determination::count());
        $this->assertSame('A. Researcher', $determination->determiner_name);
        $this->assertTrue($specimen->currentDetermination->is($determination));
        $this->assertTrue($specimen->currentDetermination->taxon->is($taxon));

        $this->assertSame(1, Preparation::count());
        $this->assertSame('3 EtOH70', $preparation->source_value);
        $this->assertSame('unparsed', $preparation->preparation_type);

        $this->assertSame([], $result['warnings']);
        $this->assertSame('created', $result['specimen']['action']);
    }

    public function test_persister_is_idempotent_for_the_same_mapped_occurrence(): void
    {
        $collection = Collection::factory()->create(['slug' => 'fish']);
        $mapped = $this->mappedOccurrence($collection, [
            'id' => 'occ-1',
            'catalogNumber' => '000123',
            'modified' => '2026-01-23 11:35:44',
            'scientificName' => 'Gadus morhua',
            'scientificNameAuthorship' => 'Linnaeus, 1758',
            'taxonRank' => 'species',
            'vernacularName' => 'Cod',
            'country' => 'United States',
            'stateProvince' => 'North Carolina',
            'county' => 'Dare County',
            'continent' => 'North America',
            'locality' => 'Cape Hatteras',
            'verbatimLocality' => 'Cape Hatteras, Dare County',
            'waterBody' => 'Pamlico Sound',
            'verbatimDepth' => '3 m',
            'decimalLatitude' => '35.1234567',
            'decimalLongitude' => '-75.1234567',
            'geodeticDatum' => 'WGS84',
            'coordinateUncertaintyInMeters' => '25.5',
            'footprintWKT' => 'POINT(-75.1234567 35.1234567)',
            'georeferencedBy' => 'Jane Doe',
            'georeferencedDate' => '2026-08-20',
            'georeferenceProtocol' => 'Gazetteer lookup',
            'fieldNumber' => 'FN-1',
            'year' => '1941',
            'month' => '5',
            'day' => '17',
            'verbatimEventDate' => '17 May 1941',
            'samplingProtocol' => 'Seine net',
            'identifiedBy' => 'A. Researcher',
            'preparations' => '3 EtOH70',
        ]);

        app(DarwinCoreOccurrencePersister::class)->persist($mapped, $collection);
        app(DarwinCoreOccurrencePersister::class)->persist($mapped, $collection);

        $this->assertSame(1, Specimen::count());
        $this->assertSame(1, CollectingEvent::count());
        $this->assertSame(1, Locality::count());
        $this->assertSame(4, Geography::count());
        $this->assertSame(1, Taxon::count());
        $this->assertSame(1, Determination::count());
        $this->assertSame(1, Preparation::count());
    }

    public function test_persister_preserves_year_only_collecting_events(): void
    {
        $collection = Collection::factory()->create(['slug' => 'fish']);
        $mapped = $this->mappedOccurrence($collection, [
            'id' => 'occ-1',
            'catalogNumber' => '000123',
            'scientificName' => 'Gadus morhua',
            'year' => '1941',
            'month' => null,
            'day' => null,
            'preparations' => null,
        ]);

        app(DarwinCoreOccurrencePersister::class)->persist($mapped, $collection);

        $event = CollectingEvent::query()->firstOrFail();

        $this->assertSame(1941, $event->event_year);
        $this->assertNull($event->event_month);
        $this->assertNull($event->event_day);
        $this->assertNull($event->event_date);
    }

    public function test_persister_preserves_year_and_month_without_inventing_a_day(): void
    {
        $collection = Collection::factory()->create(['slug' => 'fish']);
        $mapped = $this->mappedOccurrence($collection, [
            'id' => 'occ-1',
            'catalogNumber' => '000123',
            'scientificName' => 'Gadus morhua',
            'year' => '1941',
            'month' => '5',
            'day' => null,
            'preparations' => null,
        ]);

        app(DarwinCoreOccurrencePersister::class)->persist($mapped, $collection);

        $event = CollectingEvent::query()->firstOrFail();

        $this->assertSame(1941, $event->event_year);
        $this->assertSame(5, $event->event_month);
        $this->assertNull($event->event_day);
        $this->assertNull($event->event_date);
    }

    public function test_persister_stores_full_collecting_event_dates(): void
    {
        $collection = Collection::factory()->create(['slug' => 'fish']);
        $mapped = $this->mappedOccurrence($collection, [
            'id' => 'occ-1',
            'catalogNumber' => '000123',
            'scientificName' => 'Gadus morhua',
            'year' => '1941',
            'month' => '5',
            'day' => '17',
            'preparations' => null,
        ]);

        app(DarwinCoreOccurrencePersister::class)->persist($mapped, $collection);

        $event = CollectingEvent::query()->firstOrFail();

        $this->assertSame('1941-05-17', $event->event_date->format('Y-m-d'));
    }

    public function test_persister_discards_latitude_only_coordinates_and_returns_a_warning(): void
    {
        $collection = Collection::factory()->create(['slug' => 'fish']);
        $mapped = $this->mappedOccurrence($collection, [
            'id' => 'occ-1',
            'catalogNumber' => '000123',
            'scientificName' => 'Gadus morhua',
            'decimalLatitude' => '35.1234567',
            'decimalLongitude' => null,
            'preparations' => null,
        ]);

        $result = app(DarwinCoreOccurrencePersister::class)->persist($mapped, $collection);

        $locality = Locality::query()->firstOrFail();

        $this->assertNull($locality->decimal_latitude);
        $this->assertNull($locality->decimal_longitude);
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('incomplete coordinate pair', implode(' ', $result['warnings']));
    }

    public function test_persister_discards_invalid_latitude_coordinates_and_returns_a_warning(): void
    {
        $collection = Collection::factory()->create(['slug' => 'fish']);
        $mapped = $this->mappedOccurrence($collection, [
            'id' => 'occ-1',
            'catalogNumber' => '000123',
            'scientificName' => 'Gadus morhua',
            'decimalLatitude' => '181.1234567',
            'decimalLongitude' => '-75.1234567',
            'preparations' => null,
        ]);

        $result = app(DarwinCoreOccurrencePersister::class)->persist($mapped, $collection);

        $locality = Locality::query()->firstOrFail();

        $this->assertNull($locality->decimal_latitude);
        $this->assertNull($locality->decimal_longitude);
        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('invalid latitude', implode(' ', $result['warnings']));
    }

    public function test_persister_rolls_back_when_a_later_step_fails(): void
    {
        $collection = Collection::factory()->create(['slug' => 'fish']);
        $mapped = $this->mappedOccurrence($collection, [
            'id' => 'occ-1',
            'catalogNumber' => '000123',
            'scientificName' => 'Gadus morhua',
            'preparations' => '3 EtOH70',
        ]);

        $persister = new class(app(CollectionImportFileLocator::class)) extends DarwinCoreOccurrencePersister
        {
            protected function persistPreparations(Specimen $specimen, array $payload): array
            {
                throw new RuntimeException('boom');
            }
        };

        try {
            $persister->persist($mapped, $collection);
            $this->fail('Expected the persister to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        $this->assertSame(0, Specimen::count());
        $this->assertSame(0, CollectingEvent::count());
        $this->assertSame(0, Locality::count());
        $this->assertSame(0, Taxon::count());
        $this->assertSame(0, Determination::count());
        $this->assertSame(0, Preparation::count());
        $this->assertSame(0, Geography::count());
    }

    protected function mappedOccurrence(Collection $collection, array $overrides): array
    {
        $row = array_merge([
            'id' => 'occ-1',
            'catalogNumber' => '000123',
            'modified' => '2026-01-23 11:35:44',
            'scientificName' => 'Gadus morhua',
            'scientificNameAuthorship' => 'Linnaeus, 1758',
            'taxonRank' => 'species',
            'vernacularName' => 'Cod',
            'continent' => 'North America',
            'country' => 'United States',
            'stateProvince' => 'North Carolina',
            'county' => 'Dare County',
            'locality' => 'Cape Hatteras',
            'verbatimLocality' => 'Cape Hatteras, Dare County',
            'waterBody' => 'Pamlico Sound',
            'verbatimDepth' => '3 m',
            'decimalLatitude' => '35.1234567',
            'decimalLongitude' => '-75.1234567',
            'geodeticDatum' => 'WGS84',
            'coordinateUncertaintyInMeters' => '25.5',
            'footprintWKT' => 'POINT(-75.1234567 35.1234567)',
            'georeferencedBy' => 'Jane Doe',
            'georeferencedDate' => '2026-08-20',
            'georeferenceProtocol' => 'Gazetteer lookup',
            'georeferenceSources' => 'NGA gazetteer',
            'georeferenceRemarks' => 'Verified by hand',
            'fieldNumber' => 'FN-1',
            'year' => '1941',
            'month' => '5',
            'day' => '17',
            'verbatimEventDate' => '17 May 1941',
            'samplingProtocol' => 'Seine net',
            'identifiedBy' => 'A. Researcher',
            'preparations' => '3 EtOH70',
            'license' => 'CC-BY',
        ], $overrides);

        return app(DarwinCoreOccurrenceMapper::class)->map($row, $collection);
    }
}
