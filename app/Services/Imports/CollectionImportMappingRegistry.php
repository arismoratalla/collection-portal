<?php

namespace App\Services\Imports;

class CollectionImportMappingRegistry
{
    /**
     * @var array<string, string>
     */
    protected array $directMappings = [
        'id' => 'specimen.occurrence_id',
        'modified' => 'specimen.source_modified_at',
        'catalogNumber' => 'specimen.catalog_number',
        'individualCount' => 'specimen.individual_count',
        'basisOfRecord' => 'specimen.basis_of_record',
        'typeStatus' => 'specimen.type_status',
    ];

    /**
     * @var array<string, string>
     */
    protected array $domainMappings = [
        'scientificName' => 'Taxon / Determination',
        'kingdom' => 'Taxon',
        'phylum' => 'Taxon',
        'class' => 'Taxon',
        'order' => 'Taxon',
        'family' => 'Taxon',
        'genus' => 'Taxon',
        'specificEpithet' => 'Taxon',
        'infraspecificEpithet' => 'Taxon',
        'taxonRank' => 'Taxon',
        'scientificNameAuthorship' => 'Taxon',
        'identifiedBy' => 'Determination',
        'vernacularName' => 'Taxon',
        'continent' => 'Geography',
        'waterBody' => 'Geography',
        'country' => 'Geography',
        'countryCode' => 'Geography',
        'stateProvince' => 'Geography',
        'county' => 'Geography',
        'locality' => 'Locality',
        'verbatimLocality' => 'Locality',
        'verbatimDepth' => 'Locality',
        'locationRemarks' => 'Locality',
        'decimalLatitude' => 'Locality',
        'decimalLongitude' => 'Locality',
        'geodeticDatum' => 'Locality',
        'coordinateUncertaintyInMeters' => 'Locality',
        'footprintWKT' => 'Locality',
        'georeferencedBy' => 'Locality',
        'georeferencedDate' => 'Locality',
        'georeferenceProtocol' => 'Locality',
        'georeferenceSources' => 'Locality',
        'georeferenceRemarks' => 'Locality',
        'fieldNumber' => 'CollectingEvent',
        'year' => 'CollectingEvent',
        'month' => 'CollectingEvent',
        'day' => 'CollectingEvent',
        'verbatimEventDate' => 'CollectingEvent',
        'samplingProtocol' => 'CollectingEvent',
        'preparations' => 'Preparation',
    ];

    /**
     * @var array<int, string>
     */
    protected array $metadataFields = [
        'license',
        'rightsHolder',
        'accessRights',
        'bibliographicCitation',
        'references',
        'institutionID',
        'datasetID',
        'institutionCode',
        'collectionCode',
        'datasetName',
        'ownerInstitutionCode',
        'disposition',
        'otherCatalogNumbers',
        'occurrenceRemarks',
    ];

    public function classify(string $field): string
    {
        if (array_key_exists($field, $this->directMappings)) {
            return 'direct';
        }

        if (array_key_exists($field, $this->domainMappings)) {
            return 'domain';
        }

        if (in_array($field, $this->metadataFields, true)) {
            return 'metadata';
        }

        return 'unmapped';
    }

    public function target(string $field): ?string
    {
        return $this->directMappings[$field]
            ?? $this->domainMappings[$field]
            ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function directMappings(): array
    {
        return $this->directMappings;
    }

    /**
     * @return array<string, string>
     */
    public function domainMappings(): array
    {
        return $this->domainMappings;
    }

    /**
     * @return array<int, string>
     */
    public function metadataFields(): array
    {
        return $this->metadataFields;
    }
}
