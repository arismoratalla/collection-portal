<?php

use App\Models\Collection;
use App\Models\Specimen;
use App\Services\CollectionImports\CollectionImportFileLocator;
use App\Services\Imports\CollectionImportMappingRegistry;
use App\Services\Imports\CollectionImportProfiler;
use App\Services\Imports\CollectionImportRowProcessor;
use App\Services\Imports\DarwinCoreOccurrencePersister;
use App\Services\ProjectStatusService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('portal:imports {collection?}', function (?string $collection = null) {
    /** @var CollectionImportFileLocator $locator */
    $locator = app(CollectionImportFileLocator::class);
    $configuredSlugs = array_keys(config('collection-imports.collections', []));

    if ($collection !== null) {
        $configuredSlugs = [$locator->collectionSlug($collection)];
    }

    foreach ($configuredSlugs as $slug) {
        $label = Str::headline(str_replace('-', ' ', $slug));
        $files = $locator->discover($slug);

        $this->line($label);

        if ($files === []) {
            $this->line('  (no incoming CSV files)');
            $this->line('');

            continue;
        }

        foreach ($files as $file) {
            $this->line('  - '.$file);
        }

        $this->line('');
    }

    return 0;
})->purpose('List incoming collection import files');

Artisan::command('portal:profile {collection}', function (string $collection) {
    /** @var CollectionImportProfiler $profiler */
    $profiler = app(CollectionImportProfiler::class);
    /** @var CollectionImportMappingRegistry $registry */
    $registry = app(CollectionImportMappingRegistry::class);

    $reports = $profiler->profileCollection($collection);

    if ($reports === []) {
        $this->warn("No incoming CSV files found for [{$collection}].");

        return 0;
    }

    foreach ($reports as $report) {
        $this->line('Collection: '.$report['collection_name']);
        $this->line('Slug: '.$report['collection_slug']);
        $this->line('Filename: '.$report['filename']);
        $this->line('File size: '.$report['file_size'].' bytes');
        $this->line('Total rows: '.$report['total_rows']);
        $this->line('Blank rows: '.$report['blank_rows']);
        $this->line('Malformed rows: '.$report['malformed_rows']);
        $this->line('Column count: '.$report['column_count']);
        $this->line('Header names:');

        foreach ($report['headers'] as $header) {
            $this->line('  - '.$header);
        }

        $this->line('Identifier profile:');
        foreach ($report['identifiers'] as $field => $stats) {
            $line = '  - '.$field;
            foreach ($stats as $key => $value) {
                $line .= sprintf(' | %s: %s', $key, $value);
            }
            $this->line($line);
        }

        $this->line('Column summary:');
        foreach ($report['columns'] as $column) {
            $samples = $column['samples'] === [] ? '(none)' : implode(' | ', $column['samples']);
            $this->line(sprintf(
                '  - %s | non-empty: %d | empty: %d | samples: %s',
                $column['field'],
                $column['non_empty'],
                $column['empty'],
                $samples
            ));
        }

        $this->line('Mapping Summary');
        $this->line(sprintf('Direct: %d', $report['mapping']['counts']['direct']));
        foreach ($report['mapping']['direct'] as $field) {
            $this->line(sprintf('  - %-20s -> %s', $field, $registry->target($field)));
        }
        $this->line(sprintf('Domain: %d', $report['mapping']['counts']['domain']));
        foreach ($report['mapping']['domain'] as $field) {
            $this->line(sprintf('  - %-20s -> %s', $field, $registry->target($field)));
        }
        $this->line(sprintf('Metadata: %d', $report['mapping']['counts']['metadata']));
        foreach ($report['mapping']['metadata'] as $field) {
            $this->line('  - '.$field);
        }
        $this->line(sprintf('Unmapped: %d', $report['mapping']['counts']['unmapped']));
        foreach ($report['mapping']['unmapped'] as $field) {
            $this->line('  - '.$field);
        }

        $this->line('Data Quality');
        $this->line('  missing occurrence IDs: '.$report['quality']['missing_occurrence_ids']);
        $this->line('  duplicate occurrence IDs: '.$report['quality']['duplicate_occurrence_ids']);
        $this->line('  missing catalog numbers: '.$report['quality']['missing_catalog_numbers']);
        $this->line('  duplicate catalog numbers: '.$report['quality']['duplicate_catalog_numbers']);
        $this->line('  latitude-only rows: '.$report['quality']['latitude_only']);
        $this->line('  longitude-only rows: '.$report['quality']['longitude_only']);
        $this->line('  invalid latitude values: '.$report['quality']['invalid_latitude']);
        $this->line('  invalid longitude values: '.$report['quality']['invalid_longitude']);
        $this->line('  invalid individualCount values: '.$report['quality']['invalid_individual_count']);
        $this->line('  date inconsistencies: '.$report['quality']['date_inconsistencies']);

        if ($report['quality']['constant_fields'] !== []) {
            $this->line('  constant metadata fields: '.implode(', ', $report['quality']['constant_fields']));
        }

        if ($report['quality']['empty_fields'] !== []) {
            $this->line('  empty fields: '.implode(', ', $report['quality']['empty_fields']));
        }

        $this->line('');
    }

    return 0;
})->purpose('Profile incoming collection import CSV files');

Artisan::command('portal:preview {collection} {--limit=5}', function (string $collection) {
    $limit = (int) $this->option('limit');
    $limit = max(1, min(100, $limit));

    /** @var CollectionImportRowProcessor $processor */
    $processor = app(CollectionImportRowProcessor::class);

    try {
        $result = $processor->process($collection, $limit, function (
            array $mapped,
            int $rowNumber,
            int $lineNumber,
            string $path,
            Collection $collectionModel
        ): void {
            $this->line('Row '.$rowNumber);
            $this->line(json_encode($mapped, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->line('');
        });
    } catch (Throwable $throwable) {
        $this->error($throwable->getMessage());

        return 1;
    }

    if ($result['path'] === null) {
        $this->warn("No incoming CSV files found for [{$collection}].");

        return 0;
    }

    $this->line('File: '.$result['file_name']);
    $this->line('Preview limit: '.$limit);

    return 0;
})->purpose('Preview intended domain mappings for incoming collection CSV rows');

Artisan::command('portal:persist-preview {collection} {--limit=5} {--benchmark}', function (string $collection) {
    $benchmark = (bool) $this->option('benchmark');
    $maxLimit = $benchmark ? 1000 : 100;
    $limit = (int) $this->option('limit');

    if ($limit > $maxLimit) {
        $this->error('Limit must not exceed '.$maxLimit.' for this command.');

        return 1;
    }

    $limit = max(1, $limit);
    $this->line('Persist preview limit: '.$limit);

    /** @var CollectionImportRowProcessor $processor */
    $processor = app(CollectionImportRowProcessor::class);
    /** @var DarwinCoreOccurrencePersister $persister */
    $persister = app(DarwinCoreOccurrencePersister::class);

    $driver = DB::connection()->getDriverName();
    $connection = DB::connection();
    $connection->flushQueryLog();
    $connection->enableQueryLog();
    $rowsAttempted = 0;
    $created = 0;
    $updated = 0;
    $warnings = [];
    $errors = [];
    $startedAt = hrtime(true);

    if ($benchmark && $driver !== 'mysql') {
        $this->warn('Benchmark mode is running on '.$driver.'; use MySQL for comparable benchmark results.');
    }

    DB::beginTransaction();

    try {
        $result = $processor->process($collection, $limit, function (array $mapped, int $rowNumber, int $lineNumber, string $path, Collection $collectionModel) use (
            $persister,
            &$rowsAttempted,
            &$created,
            &$updated,
            &$warnings,
            &$errors
        ): void {
            $rowsAttempted++;

            try {
                $persisted = $persister->persist($mapped, $collectionModel);
                $action = $persisted['specimen']['action'];

                if ($action === 'created') {
                    $created++;
                } else {
                    $updated++;
                }

                $this->line('Row '.$rowNumber.' | specimen '.$persisted['specimen']['occurrence_id'].' | '.$action);

                foreach ($persisted['warnings'] as $warning) {
                    $warnings[] = 'Row '.$rowNumber.': '.$warning;
                    $this->warn('Warnings: '.$warning);
                }
            } catch (Throwable $throwable) {
                $errors[] = 'Row '.$rowNumber.': '.$throwable->getMessage();
                $this->error('Row '.$rowNumber.' failed: '.$throwable->getMessage());
            }
        });
    } finally {
        DB::rollBack();
        $queryCount = count($connection->getQueryLog());
        $connection->disableQueryLog();
    }

    $elapsedSeconds = max((hrtime(true) - $startedAt) / 1_000_000_000, 0.000001);

    $this->line('');
    $this->line('Rolled back preview transaction.');
    $this->line('Rows processed: '.$rowsAttempted);
    $this->line('Created: '.$created);
    $this->line('Updated: '.$updated);
    $this->line('Warnings: '.count($warnings));
    $this->line('Errors: '.count($errors));

    if ($benchmark) {
        $this->line('Benchmark mode: '.$driver);
        $this->line('Elapsed time: '.number_format($elapsedSeconds, 3).' s');
        $this->line('SQL count: '.$queryCount);
        $this->line('SQL per row: '.number_format($queryCount / max($rowsAttempted, 1), 2));
        $this->line('Rows/sec: '.number_format($rowsAttempted / $elapsedSeconds, 2));
        $this->line('Peak memory: '.number_format(memory_get_peak_usage(true) / 1_048_576, 2).' MB');
    }

    return 0;
})->purpose('Dry-run persistence for incoming collection CSV rows');

Artisan::command('portal:import {collection} {--limit=10} {--yes}', function (string $collection) {
    $limit = (int) $this->option('limit');

    if ($limit > 100) {
        $this->error('Limit must not exceed 100 for committed sample imports.');

        return 1;
    }

    $limit = max(1, $limit);

    /** @var CollectionImportFileLocator $locator */
    $locator = app(CollectionImportFileLocator::class);

    try {
        $path = $locator->firstIncomingFilePath($collection);
    } catch (Throwable $throwable) {
        $this->error($throwable->getMessage());

        return 1;
    }

    if ($path === null) {
        $this->warn("No incoming CSV files found for [{$collection}].");

        return 0;
    }

    /** @var CollectionImportRowProcessor $processor */
    $processor = app(CollectionImportRowProcessor::class);
    /** @var DarwinCoreOccurrencePersister $persister */
    $persister = app(DarwinCoreOccurrencePersister::class);

    $plannedRows = [];

    try {
        $result = $processor->process($collection, $limit, function (
            array $mapped,
            int $rowNumber,
            int $lineNumber,
            string $path,
            Collection $collectionModel
        ) use (&$plannedRows): void {
            $plannedRows[] = [
                'mapped' => $mapped,
                'row_number' => $rowNumber,
                'line_number' => $lineNumber,
                'path' => $path,
                'collection' => $collectionModel,
            ];
        });
    } catch (Throwable $throwable) {
        $this->error($throwable->getMessage());

        return 1;
    }

    $occurrenceIds = collect($plannedRows)
        ->map(fn (array $plannedRow): ?string => $plannedRow['mapped']['specimen']['occurrence_id'] ?? null)
        ->filter()
        ->values();

    $existingMatchingOccurrences = Specimen::query()
        ->where('collection_id', $result['collection']->id)
        ->whereIn('occurrence_id', $occurrenceIds->all())
        ->pluck('occurrence_id')
        ->all();

    $requestedRows = count($plannedRows);
    $expectedUpdates = count($existingMatchingOccurrences);
    $expectedCreates = max($requestedRows - $expectedUpdates, 0);

    $this->line('');
    $this->line('Collection: '.$result['collection']->name);
    $this->line('Source file: '.$result['file_name']);
    $this->line('Requested rows: '.$requestedRows);
    $this->line('Existing matching occurrences: '.$expectedUpdates);
    $this->line('Expected creates: '.$expectedCreates);
    $this->line('Expected updates: '.$expectedUpdates);
    $this->line('');

    if (! $this->option('yes')) {
        $this->error('Re-run with --yes to confirm the committed sample import.');

        return 1;
    }

    $this->warn('CONTROLLED SAMPLE IMPORT');
    $this->warn('This command permanently writes specimen data.');

    $created = 0;
    $updated = 0;
    $warnings = [];
    $errors = [];
    $committed = false;

    try {
        DB::beginTransaction();

        foreach ($plannedRows as $plannedRow) {
            $persisted = $persister->persist($plannedRow['mapped'], $plannedRow['collection']);
            $action = $persisted['specimen']['action'];

            if ($action === 'created') {
                $created++;
            } else {
                $updated++;
            }

            $this->line('Row '.$plannedRow['row_number'].' | specimen '.$persisted['specimen']['occurrence_id'].' | '.$action);

            foreach ($persisted['warnings'] as $warning) {
                $warnings[] = 'Row '.$plannedRow['row_number'].': '.$warning;
                $this->warn('Warnings: '.$warning);
            }
        }

        DB::commit();
        $committed = true;
    } catch (Throwable $throwable) {
        DB::rollBack();
        $errors[] = $throwable->getMessage();
        $this->error($throwable->getMessage());
    }

    $this->line('');
    $this->line('Source file: '.$result['file_name']);
    $this->line($committed ? 'Committed sample import complete.' : 'Committed sample import aborted.');
    $this->line('Rows processed: '.$requestedRows);
    $this->line('Created: '.$created);
    $this->line('Updated: '.$updated);
    $this->line('Warnings: '.count($warnings));
    $this->line('Errors: '.count($errors));

    return $committed ? 0 : 1;
})->purpose('Commit a controlled sample import for a collection');

Artisan::command('portal:inspect-import {collection} {--limit=10}', function (string $collection) {
    $limit = max(1, min(100, (int) $this->option('limit')));

    try {
        $collectionModel = Collection::query()
            ->where('slug', $collection)
            ->firstOrFail();
    } catch (Throwable $throwable) {
        $this->error($throwable->getMessage());

        return 1;
    }

    $specimens = $collectionModel->specimens()
        ->with([
            'collectingEvent.locality.geography.parent.parent.parent',
            'currentDetermination.taxon',
            'determinations.taxon',
            'preparations',
        ])
        ->orderBy('id')
        ->limit($limit)
        ->get();

    if ($specimens->isEmpty()) {
        $this->warn('No specimen records found for ['.$collectionModel->slug.'].');

        return 0;
    }

    $geographyIds = $specimens
        ->flatMap(function (Specimen $specimen): array {
            $ids = [];
            $geography = $specimen->collectingEvent?->locality?->geography;

            while ($geography !== null) {
                $ids[] = $geography->id;
                $geography = $geography->parent;
            }

            return $ids;
        })
        ->unique()
        ->values();

    $duplicateOccurrenceIds = $specimens
        ->pluck('occurrence_id')
        ->filter()
        ->duplicates()
        ->unique()
        ->count();

    $duplicateCatalogNumbers = $specimens
        ->pluck('catalog_number')
        ->filter()
        ->duplicates()
        ->unique()
        ->count();

    $specimensWithoutDetermination = $specimens->filter(fn (Specimen $specimen): bool => $specimen->determinations->isEmpty())->count();
    $specimensWithoutEvent = $specimens->filter(fn (Specimen $specimen): bool => $specimen->collecting_event_id === null)->count();
    $specimensWithoutPreparation = $specimens->filter(fn (Specimen $specimen): bool => $specimen->preparations->isEmpty())->count();

    $this->line('Collection: '.$collectionModel->name);
    $this->line('Sample size: '.$specimens->count());
    $this->line('');

    foreach ($specimens as $specimen) {
        $taxon = $specimen->currentDetermination?->taxon;
        $locality = $specimen->collectingEvent?->locality;
        $event = $specimen->collectingEvent;

        $this->line('Catalog Number: '.$specimen->catalog_number);
        $this->line('Occurrence ID: '.$specimen->occurrence_id);
        $this->line('Scientific Name: '.($specimen->scientific_name ?? $taxon?->scientific_name ?? '—'));
        $this->line('Taxon: '.($taxon?->scientific_name ?? '—'));
        $this->line('Locality: '.($locality?->locality ?? $locality?->verbatim_locality ?? '—'));
        $this->line('Collecting Event: '.($event?->field_number ?? '—').' | '.($event?->event_year ?? '—'));
        $this->line('Preparation Count: '.$specimen->preparations->count());
        $this->line('');
    }

    $this->line('Selected Range Counts');
    $this->line('Specimens: '.$specimens->count());
    $this->line('Taxa: '.$specimens->pluck('currentDetermination.taxon.id')->filter()->unique()->count());
    $this->line('Determinations: '.$specimens->sum(fn (Specimen $specimen): int => $specimen->determinations->count()));
    $this->line('Geography: '.$geographyIds->count());
    $this->line('Locality: '.$specimens->pluck('collectingEvent.locality_id')->filter()->unique()->count());
    $this->line('Collecting Event: '.$specimens->pluck('collecting_event_id')->filter()->unique()->count());
    $this->line('Preparation: '.$specimens->sum(fn (Specimen $specimen): int => $specimen->preparations->count()));
    $this->line('');
    $this->line('Data Quality');
    $this->line('Duplicate occurrence IDs: '.$duplicateOccurrenceIds);
    $this->line('Duplicate catalog numbers: '.$duplicateCatalogNumbers);
    $this->line('Specimens without determination: '.$specimensWithoutDetermination);
    $this->line('Specimens without event: '.$specimensWithoutEvent);
    $this->line('Specimens without preparation: '.$specimensWithoutPreparation);

    return 0;
})->purpose('Inspect committed sample import records');

Artisan::command('portal:status', function () {
    /** @var ProjectStatusService $status */
    $status = app(ProjectStatusService::class);
    $report = $status->report();

    $this->line('Research Collections Portal');
    $this->line('============================');
    $this->line('');
    $this->line('Environment');
    $this->line('-----------');
    $this->line('Laravel: '.$report['application']['laravel']);
    $this->line('PHP: '.$report['application']['php']);
    $this->line('Database: '.$report['application']['database_driver']);
    $this->line('Connection: '.$report['application']['database_connection']);
    $this->line('');
    $this->line('Collections');
    $this->line('-----------');
    $this->table(
        ['Collection', 'Imported', 'Incoming files', 'Status'],
        array_map(
            fn (array $collection): array => [
                $collection['collection']->name,
                (string) $collection['imported_specimens'],
                (string) $collection['incoming_file_count'],
                $collection['incoming_status'],
            ],
            $report['collections']
        )
    );
    $this->line('');
    $this->line('Import Pipeline');
    $this->line('---------------');

    foreach ($report['pipeline'] as $capability) {
        $prefix = $capability['state'] === 'enabled' ? '[x]' : '[ ]';
        $this->line($prefix.' '.$capability['label']);
    }

    $this->line('');
    $this->line('Next Step');
    $this->line('---------');
    $this->line($report['next_recommended_step']);

    return 0;
})->purpose('Show the current Research Collections Portal status');
