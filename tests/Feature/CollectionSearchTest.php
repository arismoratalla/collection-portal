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
use Database\Seeders\CollectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CollectionSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CollectionSeeder::class);
    }

    public function test_fish_search_route_responds_and_is_collection_scoped(): void
    {
        $fish = $this->createSpecimen(Collection::query()->where('slug', 'fish')->firstOrFail(), [
            'catalog_number' => 'FISH-12345',
            'occurrence_id' => 'fish-occ-1',
            'scientific_name' => 'Etheostoma blennioides',
            'family' => 'Percidae',
            'genus' => 'Etheostoma',
            'preparation' => '3 EtOH70',
            'field_number' => 'FN-1',
        ]);

        $this->createSpecimen(Collection::query()->where('slug', 'birds')->firstOrFail(), [
            'catalog_number' => 'BIRD-12345',
            'occurrence_id' => 'bird-occ-1',
            'scientific_name' => 'Corvus brachyrhynchos',
            'family' => 'Corvidae',
            'genus' => 'Corvus',
            'field_number' => 'FN-B1',
            'preparation' => 'skin',
        ]);

        $response = $this->get(route('collections.search', $fish->collection));

        $response->assertOk();
        $response->assertSeeText('Filter records');
        $response->assertSeeText('Search all fields');
        $response->assertSeeText('Taxonomy');
        $response->assertSeeText('Geography');
        $response->assertSeeText('Collecting Event');
        $response->assertSeeText('Preparation');
        $response->assertSeeText('Clear all');
        $response->assertSee('data-search-workspace', false);
        $response->assertSee('data-collection-map', false);
        $response->assertSee('Hide map');
        $response->assertSee('<table', false);
        $response->assertSeeText('FISH-12345');
        $response->assertDontSeeText('BIRD-12345');
    }

    public function test_search_page_shows_empty_state_when_collection_has_no_public_specimens(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();

        $response = $this->get(route('collections.search', $fish));

        $response->assertOk();
        $response->assertSeeText('No public specimen records are currently available for this collection.');
        $response->assertSeeText('0 records');
    }

    public function test_catalog_number_filtering_works(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();

        $this->createSpecimen($fish, [
            'catalog_number' => '12345',
            'occurrence_id' => 'fish-occ-1',
            'scientific_name' => 'Gadus morhua',
        ]);
        $this->createSpecimen($fish, [
            'catalog_number' => '99999',
            'occurrence_id' => 'fish-occ-2',
            'scientific_name' => 'Salmo salar',
        ]);

        $response = $this->get(route('collections.search', $fish).'?catalog_number=123');

        $response->assertOk();
        $response->assertSeeText('12345');
        $response->assertDontSeeText('99999');
    }

    public function test_scientific_name_filtering_works(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();

        $this->createSpecimen($fish, [
            'catalog_number' => '12345',
            'occurrence_id' => 'fish-occ-1',
            'scientific_name' => 'Etheostoma blennioides',
            'family' => 'Percidae',
            'genus' => 'Etheostoma',
        ]);
        $this->createSpecimen($fish, [
            'catalog_number' => '99999',
            'occurrence_id' => 'fish-occ-2',
            'scientific_name' => 'Gadus morhua',
            'family' => 'Gadidae',
            'genus' => 'Gadus',
        ]);

        $response = $this->get(route('collections.search', $fish).'?scientific_name=etheostoma');

        $response->assertOk();
        $response->assertSeeText('12345');
        $response->assertDontSeeText('99999');
    }

    public function test_family_and_genus_filters_work(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();

        $this->createSpecimen($fish, [
            'catalog_number' => '12345',
            'occurrence_id' => 'fish-occ-1',
            'scientific_name' => 'Etheostoma blennioides',
            'family' => 'Percidae',
            'genus' => 'Etheostoma',
        ]);
        $this->createSpecimen($fish, [
            'catalog_number' => '99999',
            'occurrence_id' => 'fish-occ-2',
            'scientific_name' => 'Gadus morhua',
            'family' => 'Gadidae',
            'genus' => 'Gadus',
        ]);

        $familyResponse = $this->get(route('collections.search', $fish).'?family=perc');
        $familyResponse->assertOk();
        $familyResponse->assertSeeText('12345');
        $familyResponse->assertDontSeeText('99999');

        $genusResponse = $this->get(route('collections.search', $fish).'?genus=etheostoma');
        $genusResponse->assertOk();
        $genusResponse->assertSeeText('12345');
        $genusResponse->assertDontSeeText('99999');
    }

    public function test_country_state_and_county_filters_work(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();

        $this->createSpecimen($fish, [
            'catalog_number' => '12345',
            'occurrence_id' => 'fish-occ-1',
            'country' => 'United States',
            'state' => 'North Carolina',
            'county' => 'Dare County',
            'locality' => 'Cape Hatteras',
        ]);
        $this->createSpecimen($fish, [
            'catalog_number' => '99999',
            'occurrence_id' => 'fish-occ-2',
            'country' => 'Mexico',
            'state' => 'Sonora',
            'county' => 'Hermosillo',
            'locality' => 'Bahia Kino',
        ]);

        $country = $this->get(route('collections.search', $fish).'?country=united');
        $country->assertOk();
        $country->assertSeeText('12345');
        $country->assertDontSeeText('99999');

        $state = $this->get(route('collections.search', $fish).'?state=carolina');
        $state->assertOk();
        $state->assertSeeText('12345');
        $state->assertDontSeeText('99999');

        $county = $this->get(route('collections.search', $fish).'?county=dare');
        $county->assertOk();
        $county->assertSeeText('12345');
        $county->assertDontSeeText('99999');
    }

    public function test_locality_field_number_year_range_and_preparation_filters_work(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();

        $this->createSpecimen($fish, [
            'catalog_number' => '12345',
            'occurrence_id' => 'fish-occ-1',
            'locality' => 'Cape Hatteras',
            'field_number' => 'FN-1',
            'event_year' => 1941,
            'event_month' => 5,
            'event_day' => 17,
            'preparation' => '3 EtOH70',
        ]);
        $this->createSpecimen($fish, [
            'catalog_number' => '99999',
            'occurrence_id' => 'fish-occ-2',
            'locality' => 'Pamlico Sound',
            'field_number' => 'FN-2',
            'event_year' => 1950,
            'event_month' => 7,
            'event_day' => 2,
            'preparation' => 'skin',
        ]);

        $locality = $this->get(route('collections.search', $fish).'?locality=hatteras');
        $locality->assertOk();
        $locality->assertSeeText('12345');
        $locality->assertDontSeeText('99999');

        $fieldNumber = $this->get(route('collections.search', $fish).'?field_number=FN-1');
        $fieldNumber->assertOk();
        $fieldNumber->assertSeeText('12345');
        $fieldNumber->assertDontSeeText('99999');

        $yearRange = $this->get(route('collections.search', $fish).'?year_from=1940&year_to=1942');
        $yearRange->assertOk();
        $yearRange->assertSeeText('12345');
        $yearRange->assertDontSeeText('99999');

        $preparation = $this->get(route('collections.search', $fish).'?preparation=EtOH70');
        $preparation->assertOk();
        $preparation->assertSeeText('12345');
        $preparation->assertDontSeeText('99999');
    }

    public function test_quick_search_works_and_pagination_preserves_filters(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();

        for ($index = 1; $index <= 26; $index++) {
            $this->createSpecimen($fish, [
                'catalog_number' => sprintf('FISH-%03d', $index),
                'occurrence_id' => 'fish-occ-'.$index,
                'scientific_name' => 'Etheostoma blennioides',
                'family' => 'Percidae',
                'genus' => 'Etheostoma',
                'locality' => $index === 1 ? 'Cape Hatteras' : 'Pamlico Sound',
                'field_number' => 'FN-'.$index,
                'preparation' => '3 EtOH70',
            ]);
        }

        $quickSearch = $this->get(route('collections.search', $fish).'?quick_search=hatteras');
        $quickSearch->assertOk();
        $quickSearch->assertSeeText('Cape Hatteras');

        $globalGeographySearch = $this->get(route('collections.search', $fish).'?q=united%20states');
        $globalGeographySearch->assertOk();
        $globalGeographySearch->assertSeeText('FISH-001');

        $paginated = $this->get(route('collections.search', $fish).'?scientific_name=etheostoma&per_page=25');
        $paginated->assertOk();
        $paginated->assertSeeText('26 records');
        $paginated->assertSee('scientific_name=etheostoma', false);
        $paginated->assertSee('page=2', false);
    }

    public function test_zero_result_state_is_rendered(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();

        $this->createSpecimen($fish, [
            'catalog_number' => '12345',
            'occurrence_id' => 'fish-occ-1',
            'scientific_name' => 'Etheostoma blennioides',
        ]);

        $response = $this->get(route('collections.search', $fish).'?scientific_name=does-not-exist');

        $response->assertOk();
        $response->assertSeeText('No specimen records matched your search.');
    }

    public function test_specimen_detail_loads_by_stable_identifier_and_hides_sensitive_coordinates(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();
        $specimen = $this->createSpecimen($fish, [
            'catalog_number' => 'FISH-12345',
            'occurrence_id' => 'fish-occ-1',
            'scientific_name' => 'Etheostoma blennioides',
            'family' => 'Percidae',
            'genus' => 'Etheostoma',
            'coordinates_public' => false,
            'sensitive' => true,
        ]);

        $response = $this->get(route('specimens.show', $specimen));

        $response->assertOk();
        $response->assertSeeText('FISH-12345');
        $response->assertSeeText('fish-occ-1');
        $response->assertSeeText('Etheostoma blennioides');
        $response->assertSeeText('Coordinates withheld.');
        $response->assertDontSee('35.1234567');
        $response->assertDontSee('-75.1234567');
        $response->assertDontSee('25.5');
        $response->assertDontSee(base_path());
    }

    public function test_unknown_collection_returns_404_on_search_route(): void
    {
        $this->get('/collections/unknown-collection/search')->assertNotFound();
    }

    public function test_facets_are_collection_scoped_and_count_matching_specimens(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();
        $birds = Collection::query()->where('slug', 'birds')->firstOrFail();

        $this->createSpecimen($fish, ['catalog_number' => 'F-1', 'occurrence_id' => 'facet-fish-1', 'family' => 'Percidae', 'genus' => 'Etheostoma']);
        $this->createSpecimen($fish, ['catalog_number' => 'F-2', 'occurrence_id' => 'facet-fish-2', 'family' => 'Percidae', 'genus' => 'Etheostoma']);
        $this->createSpecimen($fish, ['catalog_number' => 'F-3', 'occurrence_id' => 'facet-fish-3', 'family' => 'Gadidae', 'genus' => 'Gadus']);
        $this->createSpecimen($birds, ['catalog_number' => 'B-1', 'occurrence_id' => 'facet-bird-1', 'family' => 'Corvidae', 'genus' => 'Corvus']);

        $response = $this->getJson(route('collections.facets.show', [$fish, 'family']));

        $response->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonFragment(['value' => 'Percidae', 'count' => 2])
            ->assertJsonFragment(['value' => 'Gadidae', 'count' => 1])
            ->assertJsonMissing(['value' => 'Corvidae']);
    }

    public function test_multi_select_facets_use_or_within_a_facet_and_and_between_facets(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();

        $this->createSpecimen($fish, ['catalog_number' => 'F-1', 'occurrence_id' => 'multi-1', 'family' => 'Percidae', 'state' => 'North Carolina']);
        $this->createSpecimen($fish, ['catalog_number' => 'F-2', 'occurrence_id' => 'multi-2', 'family' => 'Gadidae', 'state' => 'North Carolina']);
        $this->createSpecimen($fish, ['catalog_number' => 'F-3', 'occurrence_id' => 'multi-3', 'family' => 'Percidae', 'state' => 'Virginia']);

        $response = $this->get(route('collections.search', $fish).'?family[]=Percidae&family[]=Gadidae&state[]=North%20Carolina');

        $response->assertOk();
        $response->assertSeeText('F-1');
        $response->assertSeeText('F-2');
        $response->assertDontSeeText('F-3');
    }

    public function test_facet_search_and_load_more_are_paginated(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();

        foreach (range(1, 21) as $index) {
            $this->createSpecimen($fish, [
                'catalog_number' => 'F-'.$index,
                'occurrence_id' => 'facet-page-'.$index,
                'family' => sprintf('Family %02d', $index),
            ]);
        }

        $searched = $this->getJson(route('collections.facets.show', [$fish, 'family']).'?facet_search=Family%2001');
        $searched->assertOk()->assertJsonPath('total', 1)->assertJsonFragment(['value' => 'Family 01']);

        $firstPage = $this->getJson(route('collections.facets.show', [$fish, 'family']));
        $firstPage->assertOk()->assertJsonPath('total', 21)->assertJsonPath('has_more', true);
        $this->assertCount(20, $firstPage->json('values'));

        $secondPage = $this->getJson(route('collections.facets.show', [$fish, 'family']).'?facet_page=2');
        $secondPage->assertOk()->assertJsonPath('page', 2)->assertJsonPath('has_more', false);
        $this->assertCount(1, $secondPage->json('values'));
    }

    public function test_map_data_only_returns_valid_public_coordinates_and_respects_filters(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();
        $birds = Collection::query()->where('slug', 'birds')->firstOrFail();

        $this->createSpecimen($fish, [
            'catalog_number' => 'PUBLIC', 'occurrence_id' => 'public-coordinate', 'family' => 'Percidae',
            'decimal_latitude' => 35.1111111, 'decimal_longitude' => -75.1111111,
        ]);
        $this->createSpecimen($fish, [
            'catalog_number' => 'PRIVATE', 'occurrence_id' => 'private-coordinate', 'coordinates_public' => false,
            'decimal_latitude' => 35.2222222, 'decimal_longitude' => -75.2222222,
        ]);
        $this->createSpecimen($fish, [
            'catalog_number' => 'SENSITIVE', 'occurrence_id' => 'sensitive-coordinate', 'sensitive' => true,
            'decimal_latitude' => 35.3333333, 'decimal_longitude' => -75.3333333,
        ]);
        $this->createSpecimen($fish, [
            'catalog_number' => 'LATITUDE-ONLY', 'occurrence_id' => 'latitude-only',
            'decimal_latitude' => 35.4444444, 'decimal_longitude' => null,
        ]);
        $this->createSpecimen($fish, [
            'catalog_number' => 'INVALID', 'occurrence_id' => 'invalid-coordinate',
            'decimal_latitude' => 91, 'decimal_longitude' => -75.5555555,
        ]);
        $this->createSpecimen($birds, [
            'catalog_number' => 'BIRD', 'occurrence_id' => 'bird-coordinate',
            'decimal_latitude' => 35.6666666, 'decimal_longitude' => -75.6666666,
        ]);

        $response = $this->getJson(route('collections.map-data', $fish).'?family[]=Percidae');

        $response->assertOk()
            ->assertJsonCount(1, 'markers')
            ->assertJsonPath('markers.0.occurrence_id', 'public-coordinate')
            ->assertJsonPath('markers.0.latitude', 35.1111111)
            ->assertJsonPath('markers.0.longitude', -75.1111111)
            ->assertDontSee('35.2222222')
            ->assertDontSee('35.3333333')
            ->assertDontSee('35.4444444')
            ->assertDontSee('91');
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function createSpecimen(Collection $collection, array $options = []): Specimen
    {
        $countryName = $options['country'] ?? 'United States';
        $stateName = $options['state'] ?? 'North Carolina';
        $countyName = $options['county'] ?? 'Dare County';
        $localityName = $options['locality'] ?? 'Cape Hatteras';
        $scientificName = $options['scientific_name'] ?? 'Etheostoma blennioides';
        $family = $options['family'] ?? 'Percidae';
        $genus = $options['genus'] ?? 'Etheostoma';

        $country = Geography::factory()->create([
            'name' => $countryName,
            'geography_type' => 'country',
            'parent_id' => null,
        ]);

        $state = Geography::factory()->create([
            'name' => $stateName,
            'geography_type' => 'state',
            'parent_id' => $country->id,
        ]);

        $county = Geography::factory()->create([
            'name' => $countyName,
            'geography_type' => 'county',
            'parent_id' => $state->id,
        ]);

        $locality = Locality::factory()->create([
            'geography_id' => $county->id,
            'locality' => $localityName,
            'verbatim_locality' => $localityName,
            'decimal_latitude' => array_key_exists('decimal_latitude', $options) ? $options['decimal_latitude'] : 35.1234567,
            'decimal_longitude' => array_key_exists('decimal_longitude', $options) ? $options['decimal_longitude'] : -75.1234567,
            'coordinate_uncertainty_meters' => $options['coordinate_uncertainty_meters'] ?? 25.5,
            'coordinates_public' => $options['coordinates_public'] ?? true,
            'sensitive' => $options['sensitive'] ?? false,
        ]);

        $event = CollectingEvent::factory()->create([
            'locality_id' => $locality->id,
            'field_number' => $options['field_number'] ?? 'FN-1',
            'event_year' => $options['event_year'] ?? 1941,
            'event_month' => $options['event_month'] ?? 5,
            'event_day' => $options['event_day'] ?? 17,
            'event_date' => $options['event_date'] ?? '1941-05-17',
            'event_date_start' => $options['event_date_start'] ?? '1941-05-17',
            'event_date_end' => $options['event_date_end'] ?? '1941-05-17',
            'verbatim_event_date' => $options['verbatim_event_date'] ?? '17 May 1941',
            'sampling_protocol' => $options['sampling_protocol'] ?? 'Seine net',
        ]);

        $taxon = Taxon::factory()->create([
            'scientific_name' => $scientificName,
            'family' => $family,
            'genus' => $genus,
            'rank' => 'species',
            'vernacular_name' => $options['vernacular_name'] ?? 'Blue darter',
        ]);

        $specimen = Specimen::factory()->create([
            'collection_id' => $collection->id,
            'collecting_event_id' => $event->id,
            'catalog_number' => $options['catalog_number'] ?? 'FISH-12345',
            'occurrence_id' => $options['occurrence_id'] ?? (string) Str::uuid(),
            'scientific_name' => $scientificName,
            'type_status' => $options['type_status'] ?? 'paratype',
            'individual_count' => $options['individual_count'] ?? 1,
            'source_modified_at' => $options['source_modified_at'] ?? '2026-01-23 11:35:44',
        ]);

        Determination::factory()->create([
            'specimen_id' => $specimen->id,
            'taxon_id' => $taxon->id,
            'determiner_name' => $options['determiner_name'] ?? 'A. Researcher',
            'determined_at' => $options['determined_at'] ?? '2026-08-20',
            'remarks' => $options['remarks'] ?? null,
            'is_current' => true,
        ]);

        Preparation::factory()->create([
            'specimen_id' => $specimen->id,
            'source_value' => $options['preparation'] ?? '3 EtOH70',
            'preparation_type' => $options['preparation_type'] ?? 'fluid-preserved',
            'count' => $options['preparation_count'] ?? 1,
            'storage_medium' => $options['storage_medium'] ?? 'ethanol',
            'storage_location' => $options['storage_location'] ?? 'Cabinet 01-01',
            'remarks' => $options['preparation_remarks'] ?? null,
        ]);

        return $specimen->fresh([
            'collection',
            'collectingEvent.locality.geography.parent.parent.parent',
            'currentDetermination.taxon',
            'preparations',
        ]);
    }
}
