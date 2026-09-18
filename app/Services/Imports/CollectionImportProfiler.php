<?php

namespace App\Services\Imports;

use App\Services\CollectionImports\CollectionImportFileLocator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CollectionImportProfiler
{
    public function __construct(
        protected CollectionImportFileLocator $locator,
        protected CollectionImportCsvStreamer $streamer,
        protected CollectionImportMappingRegistry $registry,
    ) {
        // Dependencies are injected by the container.
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function profileCollection(string $collection): array
    {
        $files = $this->locator->incomingFilePaths($collection);

        if ($files === []) {
            return [];
        }

        return array_map(fn (string $path) => $this->profileFilePath($collection, $path), $files);
    }

    /**
     * @return array<string, mixed>
     */
    public function profileFirstIncomingFile(string $collection): array
    {
        $path = $this->locator->firstIncomingFilePath($collection);

        if ($path === null) {
            throw new InvalidArgumentException("No incoming CSV files found for collection [{$collection}].");
        }

        return $this->profileFilePath($collection, $path);
    }

    /**
     * @return array<string, mixed>
     */
    public function profileFilePath(string $collection, string $relativePath): array
    {
        $disk = Storage::disk((string) config('collection-imports.disk', 'local'));
        $absolutePath = $disk->path($relativePath);
        $filename = basename($relativePath);
        $collectionSlug = $this->locator->collectionSlug($collection);

        $report = [
            'collection_slug' => $collectionSlug,
            'collection_name' => $collectionSlug,
            'filename' => $filename,
            'relative_path' => $relativePath,
            'file_size' => $disk->size($relativePath),
            'total_rows' => 0,
            'blank_rows' => 0,
            'malformed_rows' => 0,
            'column_count' => 0,
            'headers' => [],
            'identifiers' => [
                'id' => ['missing' => 0, 'duplicates' => 0],
                'catalogNumber' => ['missing' => 0, 'duplicates' => 0],
                'modified' => ['missing' => 0],
            ],
            'columns' => [],
            'quality' => [
                'missing_catalog_numbers' => 0,
                'duplicate_catalog_numbers' => 0,
                'missing_occurrence_ids' => 0,
                'duplicate_occurrence_ids' => 0,
                'latitude_only' => 0,
                'longitude_only' => 0,
                'invalid_latitude' => 0,
                'invalid_longitude' => 0,
                'invalid_individual_count' => 0,
                'date_inconsistencies' => 0,
                'constant_fields' => [],
                'empty_fields' => [],
            ],
            'mapping' => [
                'direct' => [],
                'domain' => [],
                'metadata' => [],
                'unmapped' => [],
                'counts' => [
                    'direct' => 0,
                    'domain' => 0,
                    'metadata' => 0,
                    'unmapped' => 0,
                ],
            ],
        ];

        $seenIds = [];
        $seenCatalogNumbers = [];
        $headerIndex = [];
        $this->streamer->process(
            $absolutePath,
            function (array $header) use (&$report, &$headerIndex): void {
                $report['headers'] = $header;
                $report['column_count'] = count($header);
                $headerIndex = array_flip($header);

                foreach ($header as $index => $field) {
                    $report['columns'][$index] = [
                        'field' => $field,
                        'non_empty' => 0,
                        'empty' => 0,
                        'samples' => [],
                    ];

                    $report['mapping'][$this->registry->classify($field)][] = $field;
                    $report['mapping']['counts'][$this->registry->classify($field)]++;
                }
            },
            function (array $row, int $lineNumber) use (&$report, &$seenIds, &$seenCatalogNumbers, &$headerIndex): void {
                $report['total_rows']++;

                if ($this->isBlankRow($row)) {
                    $report['blank_rows']++;
                    $this->countEmptyColumns($report, $row);

                    return;
                }

                if (count($row) !== $report['column_count']) {
                    $report['malformed_rows']++;
                }

                $normalized = $this->normalizeRow($row, $report['column_count']);
                $this->updateColumnStats($report, $normalized);
                $this->updateIdentifierStats($report, $normalized, $seenIds, $seenCatalogNumbers, $headerIndex);
                $this->updateQualityStats($report, $normalized, $lineNumber, $headerIndex);
            }
        );

        $report['identifiers']['id']['duplicates'] = $this->duplicateCount($seenIds);
        $report['identifiers']['catalogNumber']['duplicates'] = $this->duplicateCount($seenCatalogNumbers);
        $report['quality']['duplicate_occurrence_ids'] = $report['identifiers']['id']['duplicates'];
        $report['quality']['duplicate_catalog_numbers'] = $report['identifiers']['catalogNumber']['duplicates'];

        $report['columns'] = array_values($report['columns']);
        $report['mapping']['direct'] = array_unique($report['mapping']['direct']);
        $report['mapping']['domain'] = array_unique($report['mapping']['domain']);
        $report['mapping']['metadata'] = array_unique($report['mapping']['metadata']);
        $report['mapping']['unmapped'] = array_unique($report['mapping']['unmapped']);
        sort($report['mapping']['direct'], SORT_NATURAL | SORT_FLAG_CASE);
        sort($report['mapping']['domain'], SORT_NATURAL | SORT_FLAG_CASE);
        sort($report['mapping']['metadata'], SORT_NATURAL | SORT_FLAG_CASE);
        sort($report['mapping']['unmapped'], SORT_NATURAL | SORT_FLAG_CASE);

        $report['quality']['empty_fields'] = [];
        foreach ($report['columns'] as $column) {
            if ($column['non_empty'] === 0) {
                $report['quality']['empty_fields'][] = $column['field'];
            }
            if ($column['non_empty'] > 0 && count($column['samples']) === 1 && $this->isMetadataField($column['field'])) {
                $report['quality']['constant_fields'][] = $column['field'];
            }
        }

        $report['collection_name'] = Str::headline(str_replace('-', ' ', $collectionSlug));

        return $report;
    }

    /**
     * @param  array<int, string|null>  $row
     * @return array<int, string|null>
     */
    protected function normalizeRow(array $row, int $columnCount): array
    {
        if ($columnCount === 0) {
            return $row;
        }

        return array_pad(array_slice($row, 0, $columnCount), $columnCount, '');
    }

    /**
     * @param  array<int, string|null>  $row
     */
    protected function updateColumnStats(array &$report, array $row): void
    {
        foreach ($row as $index => $value) {
            if (! array_key_exists($index, $report['columns'])) {
                continue;
            }

            $value = $this->normalizeCell($value);

            if ($value === '') {
                $report['columns'][$index]['empty']++;

                continue;
            }

            $report['columns'][$index]['non_empty']++;

            if (count($report['columns'][$index]['samples']) < 3) {
                $report['columns'][$index]['samples'][] = $this->truncateValue($value);
            }
        }
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $seenIds
     * @param  array<string, int>  $seenCatalogNumbers
     * @param  array<string, int>  $headerIndex
     */
    protected function updateIdentifierStats(array &$report, array $row, array &$seenIds, array &$seenCatalogNumbers, array $headerIndex): void
    {
        if (array_key_exists('id', $headerIndex)) {
            $value = $this->normalizeCell($row[$headerIndex['id']] ?? null);

            if ($value === '') {
                $report['identifiers']['id']['missing']++;
                $report['quality']['missing_occurrence_ids']++;
            } else {
                $seenIds[$value] = ($seenIds[$value] ?? 0) + 1;
            }
        }

        if (array_key_exists('catalogNumber', $headerIndex)) {
            $value = $this->normalizeCell($row[$headerIndex['catalogNumber']] ?? null);

            if ($value === '') {
                $report['identifiers']['catalogNumber']['missing']++;
                $report['quality']['missing_catalog_numbers']++;
            } else {
                $seenCatalogNumbers[$value] = ($seenCatalogNumbers[$value] ?? 0) + 1;
            }
        }

        if (array_key_exists('modified', $headerIndex)) {
            $value = $this->normalizeCell($row[$headerIndex['modified']] ?? null);

            if ($value === '') {
                $report['identifiers']['modified']['missing']++;
            }
        }
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $headerIndex
     */
    protected function updateQualityStats(array &$report, array $row, int $lineNumber, array $headerIndex): void
    {
        if (array_key_exists('decimalLatitude', $headerIndex) || array_key_exists('decimalLongitude', $headerIndex)) {
            $latitude = array_key_exists('decimalLatitude', $headerIndex)
                ? $this->normalizeCell($row[$headerIndex['decimalLatitude']] ?? null)
                : '';
            $longitude = array_key_exists('decimalLongitude', $headerIndex)
                ? $this->normalizeCell($row[$headerIndex['decimalLongitude']] ?? null)
                : '';

            if ($latitude !== '' && $longitude === '') {
                $report['quality']['latitude_only']++;
            }

            if ($longitude !== '' && $latitude === '') {
                $report['quality']['longitude_only']++;
            }

            if ($latitude !== '' && ! is_numeric($latitude)) {
                $report['quality']['invalid_latitude']++;
            } elseif ($latitude !== '' && ((float) $latitude < -90 || (float) $latitude > 90)) {
                $report['quality']['invalid_latitude']++;
            }

            if ($longitude !== '' && ! is_numeric($longitude)) {
                $report['quality']['invalid_longitude']++;
            } elseif ($longitude !== '' && ((float) $longitude < -180 || (float) $longitude > 180)) {
                $report['quality']['invalid_longitude']++;
            }
        }

        if (array_key_exists('individualCount', $headerIndex)) {
            $count = $this->normalizeCell($row[$headerIndex['individualCount']] ?? null);
            if ($count !== '' && filter_var($count, FILTER_VALIDATE_INT) === false) {
                $report['quality']['invalid_individual_count']++;
            }
        }

        if (array_key_exists('year', $headerIndex) && array_key_exists('month', $headerIndex)) {
            $year = $this->normalizeCell($row[$headerIndex['year']] ?? null);
            $month = $this->normalizeCell($row[$headerIndex['month']] ?? null);
            $day = array_key_exists('day', $headerIndex)
                ? $this->normalizeCell($row[$headerIndex['day']] ?? null)
                : '';

            if ($year !== '' && $month !== '' && is_numeric($year) && is_numeric($month)) {
                if ((int) $month < 1 || (int) $month > 12) {
                    $report['quality']['date_inconsistencies']++;
                } elseif ($day !== '' && is_numeric($day) && ((int) $day < 1 || (int) $day > 31)) {
                    $report['quality']['date_inconsistencies']++;
                }
            }
        }
    }

    /**
     * @param  array<int, string|null>  $row
     */
    protected function countEmptyColumns(array &$report, array $row): void
    {
        foreach ($row as $index => $value) {
            if (! array_key_exists($index, $report['columns'])) {
                continue;
            }

            if ($this->normalizeCell($value) === '') {
                $report['columns'][$index]['empty']++;
            }
        }
    }

    protected function normalizeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    protected function truncateValue(string $value, int $limit = 80): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit - 1).'…';
    }

    protected function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->normalizeCell($value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function isMetadataField(string $field): bool
    {
        return in_array($field, $this->registry->metadataFields(), true);
    }

    /**
     * @param  array<string, int>  $seen
     */
    protected function duplicateCount(array $seen): int
    {
        $duplicates = 0;

        foreach ($seen as $count) {
            if ($count > 1) {
                $duplicates += $count - 1;
            }
        }

        return $duplicates;
    }
}
