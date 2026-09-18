<?php

namespace App\Services\Imports;

use App\Models\CollectingEvent;
use App\Models\Collection;
use App\Models\Determination;
use App\Models\Geography;
use App\Models\Locality;
use App\Models\Preparation;
use App\Models\Specimen;
use App\Models\Taxon;
use App\Services\CollectionImports\CollectionImportFileLocator;
use Illuminate\Support\Arr;
use Illuminate\Support\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class DarwinCoreOccurrencePersister
{
    /**
     * @var array<string, Collection>
     */
    protected array $collectionCache = [];

    /**
     * @var array<string, Geography>
     */
    protected array $geographyCache = [];

    /**
     * @var array<string, Locality>
     */
    protected array $localityCache = [];

    /**
     * @var array<string, CollectingEvent>
     */
    protected array $collectingEventCache = [];

    /**
     * @var array<string, Taxon>
     */
    protected array $taxonCache = [];

    /**
     * @var array<string, Determination>
     */
    protected array $determinationCache = [];

    /**
     * @var array<string, Preparation>
     */
    protected array $preparationCache = [];

    public function __construct(
        protected CollectionImportFileLocator $locator,
    ) {
        // Dependencies are injected by the container.
    }

    /**
     * Persist one mapped Darwin Core occurrence inside a transaction.
     *
     * @param  array<string, mixed>  $mapped
     * @return array<string, mixed>
     */
    public function persist(array $mapped, Collection|string $collection): array
    {
        $collection = $this->resolveCollection($collection);

        return DB::transaction(function () use ($mapped, $collection): array {
            $warnings = [];

            $specimenPayload = Arr::get($mapped, 'specimen', []);
            $taxonPayload = Arr::get($mapped, 'taxon', []);
            $localityPayload = Arr::get($mapped, 'locality', []);
            $collectingEventPayload = Arr::get($mapped, 'collecting_event', []);
            $determinationPayload = Arr::get($mapped, 'determination', []);
            $preparationPayload = Arr::get($mapped, 'preparations', []);
            $metadataPayload = Arr::get($mapped, 'metadata', []);

            $specimenResult = $this->persistSpecimen($collection, $specimenPayload);
            $geographyResults = $this->persistGeographies($localityPayload);
            $warnings = array_merge($warnings, $geographyResults['warnings']);

            $localityResult = $this->persistLocality($geographyResults['deepest'], $localityPayload);
            $warnings = array_merge($warnings, $localityResult['warnings']);

            $collectingEventResult = $this->persistCollectingEvent($localityResult['model'], $collectingEventPayload);
            $specimen = $specimenResult['model'];
            $specimen->forceFill([
                'collecting_event_id' => $collectingEventResult['model']->id,
            ])->save();

            $taxonResult = $this->persistTaxon($taxonPayload);
            $determinationResult = $taxonResult['model'] instanceof Taxon
                ? $this->persistDetermination($specimen, $taxonResult['model'], $determinationPayload)
                : ['model' => null, 'action' => null, 'warnings' => []];

            $warnings = array_merge(
                $warnings,
                $taxonResult['warnings'],
                $determinationResult['warnings'],
            );

            $preparationResults = $this->persistPreparations($specimen, $preparationPayload);
            $warnings = array_merge($warnings, $preparationResults['warnings']);

            return [
                'collection' => [
                    'id' => $collection->id,
                    'slug' => $collection->slug,
                ],
                'specimen' => [
                    'id' => $specimen->id,
                    'action' => $specimen->wasRecentlyCreated ? 'created' : 'updated',
                    'occurrence_id' => $specimen->occurrence_id,
                ],
                'related' => [
                    'geographies' => $geographyResults['records'],
                    'locality' => $localityResult['record'],
                    'collecting_event' => $collectingEventResult['record'],
                    'taxon' => $taxonResult['record'],
                    'determination' => $determinationResult['record'],
                    'preparations' => $preparationResults['records'],
                    'metadata' => array_filter(
                        $metadataPayload,
                        fn (mixed $value): bool => $value !== null && $value !== ''
                    ),
                ],
                'warnings' => array_values(array_unique($warnings)),
            ];
        });
    }

    protected function resolveCollection(Collection|string $collection): Collection
    {
        if ($collection instanceof Collection) {
            $this->collectionCache[$collection->slug] = $collection;

            return $collection;
        }

        $slug = $this->locator->collectionSlug($collection);

        if (isset($this->collectionCache[$slug])) {
            return $this->collectionCache[$slug];
        }

        return $this->collectionCache[$slug] = Collection::query()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{model: Specimen, warnings: array<int, string>}
     */
    protected function persistSpecimen(Collection $collection, array $payload): array
    {
        $occurrenceId = $this->stringOrNull($payload['occurrence_id'] ?? null);

        if ($occurrenceId === null) {
            throw new InvalidArgumentException('A specimen occurrence_id is required.');
        }

        $catalogNumber = $this->stringOrNull($payload['catalog_number'] ?? null);

        if ($catalogNumber === null) {
            throw new InvalidArgumentException('A specimen catalog_number is required.');
        }

        $specimen = Specimen::query()->firstOrNew([
            'occurrence_id' => $occurrenceId,
        ]);

        $specimen->fill([
            'collection_id' => $collection->id,
            'catalog_number' => $catalogNumber,
            'scientific_name' => $this->stringOrNull($payload['scientific_name'] ?? null),
            'basis_of_record' => $this->stringOrNull($payload['basis_of_record'] ?? null),
            'type_status' => $this->stringOrNull($payload['type_status'] ?? null),
            'individual_count' => $this->integerOrNull($payload['individual_count'] ?? null),
            'source_modified_at' => $this->stringOrNull($payload['source_modified_at'] ?? null),
        ]);

        if ($specimen->isDirty()) {
            $specimen->save();
        }

        return [
            'model' => $specimen,
            'warnings' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{records: array<int, array<string, mixed>>, deepest: ?Geography, warnings: array<int, string>}
     */
    protected function persistGeographies(array $payload): array
    {
        $records = [];
        $warnings = [];
        $parentId = null;
        $deepest = null;

        foreach ([
            ['type' => 'continent', 'name' => $this->stringOrNull($payload['continent'] ?? null), 'iso_code' => null],
            ['type' => 'country', 'name' => $this->stringOrNull($payload['country'] ?? null), 'iso_code' => $this->stringOrNull($payload['country_code'] ?? null)],
            ['type' => 'state', 'name' => $this->stringOrNull($payload['state_province'] ?? null), 'iso_code' => null],
            ['type' => 'county', 'name' => $this->stringOrNull($payload['county'] ?? null), 'iso_code' => null],
        ] as $level) {
            if ($level['name'] === null) {
                continue;
            }

            $geography = $this->resolveGeographyNode($parentId, $level['type'], $level['name'], $level['iso_code']);

            $records[] = [
                'id' => $geography->id,
                'action' => $geography->wasRecentlyCreated ? 'created' : 'updated',
                'name' => $geography->name,
                'geography_type' => $geography->geography_type,
            ];

            $parentId = $geography->id;
            $deepest = $geography;
        }

        return [
            'records' => $records,
            'deepest' => $deepest,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{model: Locality, record: array<string, mixed>, warnings: array<int, string>}
     */
    protected function persistLocality(?Geography $geography, array $payload): array
    {
        [$latitude, $longitude, $warnings] = $this->normalizeCoordinates($payload);
        $georeferencedDate = $this->normalizeDate($payload['georeferenced_date'] ?? null, 'georeferenced_date', $warnings);

        $attributes = [
            'geography_id' => $geography?->id,
            'verbatim_locality' => $this->stringOrNull($payload['verbatim_locality'] ?? null),
            'locality' => $this->stringOrNull($payload['locality'] ?? null),
            'decimal_latitude' => $latitude,
            'decimal_longitude' => $longitude,
            'coordinate_uncertainty_meters' => $this->decimalOrNull($payload['coordinate_uncertainty_meters'] ?? null),
            'geodetic_datum' => $this->stringOrNull($payload['geodetic_datum'] ?? null),
            'georeference_sources' => $this->stringOrNull($payload['georeference_sources'] ?? null),
            'georeference_remarks' => $this->stringOrNull($payload['georeference_remarks'] ?? null),
            'water_body' => $this->stringOrNull($payload['water_body'] ?? null),
            'verbatim_depth' => $this->stringOrNull($payload['verbatim_depth'] ?? null),
            'footprint_wkt' => $this->stringOrNull($payload['footprint_wkt'] ?? null),
            'georeferenced_by' => $this->stringOrNull($payload['georeferenced_by'] ?? null),
            'georeferenced_date' => $georeferencedDate,
            'georeference_protocol' => $this->stringOrNull($payload['georeference_protocol'] ?? null),
            'coordinates_public' => true,
            'sensitive' => false,
        ];

        $lookup = $attributes;
        unset($lookup['georeferenced_date']);

        $locality = $this->resolveLocality($lookup, $attributes);

        return [
            'model' => $locality,
            'record' => [
                'id' => $locality->id,
                'action' => $locality->wasRecentlyCreated ? 'created' : 'updated',
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{model: CollectingEvent, record: array<string, mixed>, warnings: array<int, string>}
     */
    protected function persistCollectingEvent(Locality $locality, array $payload): array
    {
        $warnings = [];
        $year = $this->integerOrNull($payload['event_year'] ?? $payload['year'] ?? null);
        $month = $this->boundedIntegerOrNull($payload['event_month'] ?? $payload['month'] ?? null, 1, 12);
        $day = $this->boundedIntegerOrNull($payload['event_day'] ?? $payload['day'] ?? null, 1, 31);

        if ($month === null && $this->integerOrNull($payload['month'] ?? null) !== null) {
            $warnings[] = 'Discarded invalid collecting event month.';
            $day = null;
        }

        if ($day === null && $this->integerOrNull($payload['day'] ?? null) !== null) {
            $warnings[] = 'Discarded invalid collecting event day.';
        }

        if ($month === null && $day !== null) {
            $warnings[] = 'Discarded collecting event day without a month.';
            $day = null;
        }

        $eventDate = $this->normalizeEventDate($year, $month, $day, $warnings);

        $attributes = [
            'locality_id' => $locality->id,
            'field_number' => $this->stringOrNull($payload['field_number'] ?? null),
            'event_year' => $year,
            'event_month' => $month,
            'event_day' => $day,
            'event_date' => $eventDate,
            'event_date_start' => $eventDate,
            'event_date_end' => $eventDate,
            'verbatim_event_date' => $this->stringOrNull($payload['verbatim_event_date'] ?? null),
            'sampling_protocol' => $this->stringOrNull($payload['sampling_protocol'] ?? null),
            'habitat' => $this->stringOrNull($payload['habitat'] ?? null),
            'field_notes' => $this->stringOrNull($payload['field_notes'] ?? null),
        ];

        $lookup = [
            'locality_id' => $attributes['locality_id'],
            'field_number' => $attributes['field_number'],
            'event_year' => $attributes['event_year'],
            'event_month' => $attributes['event_month'],
            'event_day' => $attributes['event_day'],
            'verbatim_event_date' => $attributes['verbatim_event_date'],
            'sampling_protocol' => $attributes['sampling_protocol'],
        ];

        $event = $this->resolveCollectingEvent($lookup, $attributes);

        return [
            'model' => $event,
            'record' => [
                'id' => $event->id,
                'action' => $event->wasRecentlyCreated ? 'created' : 'updated',
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{model: ?Taxon, record: ?array<string, mixed>, warnings: array<int, string>}
     */
    protected function persistTaxon(array $payload): array
    {
        $warnings = [];
        $scientificName = $this->stringOrNull($payload['scientific_name'] ?? null);

        if ($scientificName === null) {
            return [
                'model' => null,
                'record' => null,
                'warnings' => [],
            ];
        }

        $lookup = array_filter([
            'scientific_name' => $scientificName,
            'authorship' => $this->stringOrNull($payload['authorship'] ?? null),
            'rank' => $this->stringOrNull($payload['rank'] ?? null),
            'kingdom' => $this->stringOrNull($payload['kingdom'] ?? null),
            'phylum' => $this->stringOrNull($payload['phylum'] ?? null),
            'class_name' => $this->stringOrNull($payload['class_name'] ?? null),
            'order_name' => $this->stringOrNull($payload['order_name'] ?? null),
            'family' => $this->stringOrNull($payload['family'] ?? null),
            'genus' => $this->stringOrNull($payload['genus'] ?? null),
            'specific_epithet' => $this->stringOrNull($payload['specific_epithet'] ?? null),
            'infraspecific_epithet' => $this->stringOrNull($payload['infraspecific_epithet'] ?? null),
            'source' => $this->stringOrNull($payload['source'] ?? null),
            'source_identifier' => $this->stringOrNull($payload['source_identifier'] ?? null),
        ], static fn (mixed $value): bool => $value !== null);

        $attributes = [
            'scientific_name' => $scientificName,
            'canonical_name' => $this->stringOrNull($payload['canonical_name'] ?? null),
            'authorship' => $this->stringOrNull($payload['authorship'] ?? null),
            'rank' => $this->stringOrNull($payload['rank'] ?? null),
            'kingdom' => $this->stringOrNull($payload['kingdom'] ?? null),
            'phylum' => $this->stringOrNull($payload['phylum'] ?? null),
            'class_name' => $this->stringOrNull($payload['class_name'] ?? null),
            'order_name' => $this->stringOrNull($payload['order_name'] ?? null),
            'family' => $this->stringOrNull($payload['family'] ?? null),
            'genus' => $this->stringOrNull($payload['genus'] ?? null),
            'specific_epithet' => $this->stringOrNull($payload['specific_epithet'] ?? null),
            'infraspecific_epithet' => $this->stringOrNull($payload['infraspecific_epithet'] ?? null),
            'vernacular_name' => $this->stringOrNull($payload['vernacular_name'] ?? null),
            'source' => $this->stringOrNull($payload['source'] ?? null),
            'source_identifier' => $this->stringOrNull($payload['source_identifier'] ?? null),
            'is_accepted' => true,
        ];

        $taxon = $this->resolveTaxon($lookup, $attributes);

        return [
            'model' => $taxon,
            'record' => [
                'id' => $taxon->id,
                'action' => $taxon->wasRecentlyCreated ? 'created' : 'updated',
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{model: ?Determination, record: ?array<string, mixed>, warnings: array<int, string>}
     */
    protected function persistDetermination(Specimen $specimen, Taxon $taxon, array $payload): array
    {
        $warnings = [];
        $determinerName = $this->stringOrNull($payload['identified_by'] ?? null);

        $currentDetermination = $this->currentDeterminationFor($specimen);
        if ($currentDetermination !== null
            && $currentDetermination->taxon_id === $taxon->id
            && $currentDetermination->determiner_name === $determinerName) {
            $this->determinationCache[$this->cacheKey(['current', $specimen->id])] = $currentDetermination;

            return [
                'model' => $currentDetermination,
                'record' => [
                    'id' => $currentDetermination->id,
                    'action' => 'reused',
                ],
                'warnings' => [],
            ];
        }

        if ($currentDetermination !== null) {
            $currentDetermination->forceFill(['is_current' => false])->save();
        }

        $existing = $this->resolveDetermination(
            $specimen->id,
            $taxon->id,
            $determinerName,
            null,
            null
        );

        if ($existing !== null) {
            if (! $existing->is_current) {
                $existing->forceFill(['is_current' => true])->save();
            }

            $this->determinationCache[$this->cacheKey(['current', $specimen->id])] = $existing;

            return [
                'model' => $existing,
                'record' => [
                    'id' => $existing->id,
                    'action' => 'reused',
                ],
                'warnings' => [],
            ];
        }

        $determination = Determination::query()->create([
            'specimen_id' => $specimen->id,
            'taxon_id' => $taxon->id,
            'determiner_name' => $determinerName,
            'determined_at' => null,
            'remarks' => null,
            'is_current' => true,
        ]);

        $this->determinationCache[$this->cacheKey(['current', $specimen->id])] = $determination;
        $this->determinationCache[$this->determinationCacheKey(
            $specimen->id,
            $taxon->id,
            $determinerName,
            null,
            null
        )] = $determination;

        return [
            'model' => $determination,
            'record' => [
                'id' => $determination->id,
                'action' => 'created',
            ],
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{records: array<int, array<string, mixed>>, warnings: array<int, string>}
     */
    protected function persistPreparations(Specimen $specimen, array $payload): array
    {
        $warnings = [];
        $raw = $this->stringOrNull($payload['raw'] ?? null);

        if ($raw === null) {
            return [
                'records' => [],
                'warnings' => [],
            ];
        }

        $cacheKey = $this->preparationCacheKey($specimen->id, $raw);
        $preparation = $this->preparationCache[$cacheKey] ?? null;

        if ($preparation === null) {
            $preparation = Preparation::query()->firstOrNew([
                'specimen_id' => $specimen->id,
                'source_value' => $raw,
            ]);
        }

        $preparation->fill([
            'preparation_type' => 'unparsed',
            'count' => null,
            'storage_medium' => null,
            'storage_location' => null,
            'source_value' => $raw,
            'remarks' => null,
        ]);

        if ($preparation->isDirty()) {
            $preparation->save();
        }

        $this->preparationCache[$cacheKey] = $preparation;

        return [
            'records' => [[
                'id' => $preparation->id,
                'action' => $preparation->wasRecentlyCreated ? 'created' : 'reused',
            ]],
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{0:?string,1:?string,2:array<int, string>}
     */
    protected function normalizeCoordinates(array $payload): array
    {
        $warnings = [];
        $latitude = $this->decimalOrNull($payload['decimal_latitude'] ?? null);
        $longitude = $this->decimalOrNull($payload['decimal_longitude'] ?? null);

        $latitudeValid = $this->isValidLatitude($latitude);
        $longitudeValid = $this->isValidLongitude($longitude);

        if ($latitude === null && $longitude === null) {
            return [null, null, $warnings];
        }

        if ($latitude !== null && ! $latitudeValid) {
            $warnings[] = 'Discarded invalid latitude.';
            $latitude = null;
        }

        if ($longitude !== null && ! $longitudeValid) {
            $warnings[] = 'Discarded invalid longitude.';
            $longitude = null;
        }

        if ($latitude === null || $longitude === null) {
            if ($latitude !== null || $longitude !== null) {
                $warnings[] = 'Discarded incomplete coordinate pair.';
            }

            return [null, null, $warnings];
        }

        return [$latitude, $longitude, $warnings];
    }

    /**
     * @param  array<int, string>  $warnings
     */
    protected function normalizeEventDate(?int $year, ?int $month, ?int $day, array &$warnings): ?string
    {
        if ($year === null) {
            return null;
        }

        if ($month === null || $day === null) {
            return null;
        }

        if (! checkdate($month, $day, $year)) {
            $warnings[] = 'Discarded invalid collecting event date.';

            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    protected function normalizeDate(mixed $value, string $fieldName, array &$warnings): ?string
    {
        $string = $this->stringOrNull($value);

        if ($string === null) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $string) === 1) {
            return $string;
        }

        try {
            return CarbonImmutable::parse($string)->toDateString();
        } catch (Throwable) {
            $warnings[] = 'Discarded invalid '.$fieldName.'.';

            return null;
        }
    }

    protected function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function integerOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    protected function boundedIntegerOrNull(mixed $value, int $min, int $max): ?int
    {
        $integer = $this->integerOrNull($value);

        if ($integer === null) {
            return null;
        }

        if ($integer < $min || $integer > $max) {
            return null;
        }

        return $integer;
    }

    protected function decimalOrNull(mixed $value): ?string
    {
        $string = $this->stringOrNull($value);

        if ($string === null) {
            return null;
        }

        if (! is_numeric($string)) {
            return null;
        }

        return $string;
    }

    protected function isValidLatitude(?string $value): bool
    {
        return $value !== null && $value >= -90 && $value <= 90;
    }

    protected function isValidLongitude(?string $value): bool
    {
        return $value !== null && $value >= -180 && $value <= 180;
    }

    protected function resolveGeographyNode(?int $parentId, string $type, string $name, ?string $isoCode): Geography
    {
        $lookup = [
            'parent_id' => $parentId,
            'name' => $name,
            'geography_type' => $type,
            'iso_code' => $isoCode,
        ];

        $cacheKey = $this->cacheKey($lookup);

        if (isset($this->geographyCache[$cacheKey])) {
            $geography = $this->geographyCache[$cacheKey];
            $geography->fill($lookup);

            if ($geography->isDirty()) {
                $geography->save();
            }

            return $geography;
        }

        $geography = Geography::query()->firstOrNew($lookup);
        $geography->fill($lookup);

        if ($geography->isDirty()) {
            $geography->save();
        }

        return $this->geographyCache[$cacheKey] = $geography;
    }

    /**
     * @param  array<string, mixed>  $lookup
     * @param  array<string, mixed>  $attributes
     */
    protected function resolveLocality(array $lookup, array $attributes): Locality
    {
        $cacheKey = $this->cacheKey($lookup);

        if (isset($this->localityCache[$cacheKey])) {
            $locality = $this->localityCache[$cacheKey];
            $locality->fill($attributes);

            if ($locality->isDirty()) {
                $locality->save();
            }

            return $locality;
        }

        $locality = Locality::query()->firstOrNew($lookup);
        $locality->fill($attributes);

        if ($locality->isDirty()) {
            $locality->save();
        }

        return $this->localityCache[$cacheKey] = $locality;
    }

    /**
     * @param  array<string, mixed>  $lookup
     * @param  array<string, mixed>  $attributes
     */
    protected function resolveCollectingEvent(array $lookup, array $attributes): CollectingEvent
    {
        $cacheKey = $this->cacheKey($lookup);

        if (isset($this->collectingEventCache[$cacheKey])) {
            $event = $this->collectingEventCache[$cacheKey];
            $event->fill($attributes);

            if ($event->isDirty()) {
                $event->save();
            }

            return $event;
        }

        $event = CollectingEvent::query()->firstOrNew($lookup);
        $event->fill($attributes);

        if ($event->isDirty()) {
            $event->save();
        }

        return $this->collectingEventCache[$cacheKey] = $event;
    }

    /**
     * @param  array<string, mixed>  $lookup
     * @param  array<string, mixed>  $attributes
     */
    protected function resolveTaxon(array $lookup, array $attributes): Taxon
    {
        $cacheKey = $this->cacheKey($lookup);

        if (isset($this->taxonCache[$cacheKey])) {
            $taxon = $this->taxonCache[$cacheKey];
            $taxon->fill($attributes);

            if ($taxon->isDirty()) {
                $taxon->save();
            }

            return $taxon;
        }

        $taxon = Taxon::query()->firstOrNew($lookup);
        $taxon->fill($attributes);

        if ($taxon->isDirty()) {
            $taxon->save();
        }

        return $this->taxonCache[$cacheKey] = $taxon;
    }

    protected function currentDeterminationFor(Specimen $specimen): ?Determination
    {
        $cacheKey = $this->cacheKey(['current', $specimen->id]);

        if (array_key_exists($cacheKey, $this->determinationCache)) {
            return $this->determinationCache[$cacheKey];
        }

        return $this->determinationCache[$cacheKey] = $specimen->currentDetermination()->first();
    }

    protected function resolveDetermination(int $specimenId, int $taxonId, ?string $determinerName, ?string $determinedAt, ?string $remarks): ?Determination
    {
        $cacheKey = $this->determinationCacheKey($specimenId, $taxonId, $determinerName, $determinedAt, $remarks);

        if (array_key_exists($cacheKey, $this->determinationCache)) {
            return $this->determinationCache[$cacheKey];
        }

        return $this->determinationCache[$cacheKey] = Determination::query()
            ->where('specimen_id', $specimenId)
            ->where('taxon_id', $taxonId)
            ->where('determiner_name', $determinerName)
            ->whereNull('determined_at')
            ->whereNull('remarks')
            ->first();
    }

    protected function preparationCacheKey(int $specimenId, string $raw): string
    {
        return $this->cacheKey([$specimenId, $raw]);
    }

    protected function determinationCacheKey(int $specimenId, int $taxonId, ?string $determinerName, ?string $determinedAt, ?string $remarks): string
    {
        return $this->cacheKey([$specimenId, $taxonId, $determinerName, $determinedAt, $remarks]);
    }

    /**
     * @param  array<int, mixed>  $parts
     */
    protected function cacheKey(array $parts): string
    {
        return sha1(serialize($parts));
    }
}
