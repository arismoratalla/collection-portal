<?php

namespace App\Services\Imports;

use App\Models\Collection;
use App\Services\CollectionImports\CollectionImportFileLocator;

class DarwinCoreOccurrenceMapper
{
    public function __construct(
        protected CollectionImportMappingRegistry $registry,
        protected CollectionImportFileLocator $locator,
    ) {
        // Dependencies are injected by the container.
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function map(array $row, Collection|string $collection): array
    {
        $this->locator->collectionSlug($collection);

        $value = fn (string $field): ?string => $this->stringOrNull($row[$field] ?? null);

        return [
            'collection' => $collection instanceof Collection ? $collection->slug : $collection,
            'specimen' => [
                'occurrence_id' => $value('id'),
                'catalog_number' => $value('catalogNumber'),
                'source_modified_at' => $value('modified'),
                'individual_count' => $value('individualCount'),
                'basis_of_record' => $value('basisOfRecord'),
                'type_status' => $value('typeStatus'),
            ],
            'taxon' => [
                'scientific_name' => $value('scientificName'),
                'kingdom' => $value('kingdom'),
                'phylum' => $value('phylum'),
                'class_name' => $value('class'),
                'order_name' => $value('order'),
                'family' => $value('family'),
                'genus' => $value('genus'),
                'specific_epithet' => $value('specificEpithet'),
                'infraspecific_epithet' => $value('infraspecificEpithet'),
                'rank' => $value('taxonRank'),
                'authorship' => $value('scientificNameAuthorship'),
                'vernacular_name' => $value('vernacularName'),
            ],
            'locality' => [
                'continent' => $value('continent'),
                'water_body' => $value('waterBody'),
                'country' => $value('country'),
                'country_code' => $value('countryCode'),
                'state_province' => $value('stateProvince'),
                'county' => $value('county'),
                'locality' => $value('locality'),
                'verbatim_locality' => $value('verbatimLocality'),
                'verbatim_depth' => $value('verbatimDepth'),
                'location_remarks' => $value('locationRemarks'),
                'decimal_latitude' => $value('decimalLatitude'),
                'decimal_longitude' => $value('decimalLongitude'),
                'geodetic_datum' => $value('geodeticDatum'),
                'coordinate_uncertainty_in_meters' => $value('coordinateUncertaintyInMeters'),
                'footprint_wkt' => $value('footprintWKT'),
                'georeferenced_by' => $value('georeferencedBy'),
                'georeferenced_date' => $value('georeferencedDate'),
                'georeference_protocol' => $value('georeferenceProtocol'),
                'georeference_sources' => $value('georeferenceSources'),
                'georeference_remarks' => $value('georeferenceRemarks'),
            ],
            'collecting_event' => [
                'field_number' => $value('fieldNumber'),
                'year' => $value('year'),
                'month' => $value('month'),
                'day' => $value('day'),
                'verbatim_event_date' => $value('verbatimEventDate'),
                'sampling_protocol' => $value('samplingProtocol'),
            ],
            'determination' => [
                'identified_by' => $value('identifiedBy'),
            ],
            'preparations' => [
                'raw' => $value('preparations'),
            ],
            'metadata' => $this->metadata($row),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, ?string>
     */
    protected function metadata(array $row): array
    {
        $metadata = [];

        foreach ($this->registry->metadataFields() as $field) {
            $metadata[$field] = $this->stringOrNull($row[$field] ?? null);
        }

        return $metadata;
    }

    protected function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
