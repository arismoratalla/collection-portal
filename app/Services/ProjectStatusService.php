<?php

namespace App\Services;

use App\Models\Collection;
use App\Services\CollectionImports\CollectionImportFileLocator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProjectStatusService
{
    public function __construct(
        protected CollectionImportFileLocator $locator,
    ) {
        // Dependencies are injected by the container.
    }

    public function application(): array
    {
        return [
            'laravel' => app()->version(),
            'php' => PHP_VERSION,
            'database_driver' => $this->databaseDriver(),
            'database_connection' => $this->databaseConnectionStatus(),
        ];
    }

    public function collections(): array
    {
        return Collection::query()
            ->where('is_active', true)
            ->withCount('specimens')
            ->orderBy('name')
            ->get()
            ->map(fn (Collection $collection): array => $this->collectionSummary($collection))
            ->all();
    }

    public function collectionSummary(Collection $collection): array
    {
        $files = $this->incomingFiles($collection);
        $imported = (int) ($collection->specimens_count ?? $collection->specimens()->count());

        return [
            'collection' => $collection,
            'imported_specimens' => $imported,
            'incoming_files' => $files,
            'incoming_file_count' => count($files),
            'incoming_status' => $this->incomingStatus($imported, count($files)),
        ];
    }

    public function collectionDetail(Collection $collection): array
    {
        return $this->collectionSummary(
            $collection->loadCount('specimens')
        );
    }

    public function workflowSteps(): array
    {
        return [
            [
                'step' => 1,
                'title' => 'Check project state',
                'command' => 'php artisan portal:status',
            ],
            [
                'step' => 2,
                'title' => 'Place updated exporter CSV under',
                'command' => 'storage/app/private/collection-imports/{collection}/incoming/',
            ],
            [
                'step' => 3,
                'title' => 'Discover it',
                'command' => 'php artisan portal:imports',
                'alt_command' => 'php artisan portal:imports fish',
            ],
            [
                'step' => 4,
                'title' => 'Profile it',
                'command' => 'php artisan portal:profile fish',
            ],
            [
                'step' => 5,
                'title' => 'Preview mapping',
                'command' => 'php artisan portal:preview fish --limit=5',
            ],
            [
                'step' => 6,
                'title' => 'Validate rollback performance',
                'command' => 'php artisan portal:persist-preview fish --limit=1000 --benchmark',
                'note' => 'Use MySQL for benchmark numbers; all database changes are rolled back.',
            ],
            [
                'step' => 7,
                'title' => 'Controlled sample import',
                'command' => 'php artisan portal:import fish --limit=10 --yes',
                'note' => 'Requires explicit confirmation with --yes.',
            ],
            [
                'step' => 8,
                'title' => 'Inspect committed sample',
                'command' => 'php artisan portal:inspect-import fish --limit=10',
            ],
            [
                'step' => 9,
                'title' => 'Repeat the sample import',
                'command' => 'php artisan portal:import fish --limit=10 --yes',
            ],
            [
                'step' => 10,
                'title' => 'Review counts before expanding',
                'note' => 'Confirm counts do not increase unexpectedly before moving to 100 records.',
            ],
        ];
    }

    public function pipelineCapabilities(): array
    {
        return [
            ['label' => 'Collection configuration', 'state' => 'enabled'],
            ['label' => 'Incoming file discovery', 'state' => 'enabled'],
            ['label' => 'CSV profiling', 'state' => 'enabled'],
            ['label' => 'Mapping preview', 'state' => 'enabled'],
            ['label' => 'Persistence rollback preview', 'state' => 'enabled'],
            ['label' => 'Controlled committed import', 'state' => 'enabled'],
            ['label' => 'Import provenance/batch ledger', 'state' => 'planned'],
            ['label' => 'Archive workflow', 'state' => 'planned'],
            ['label' => 'Public specimen search', 'state' => 'enabled'],
            ['label' => 'Media', 'state' => 'planned'],
        ];
    }

    public function nextRecommendedStep(): string
    {
        return 'Review the controlled sample import implementation, then run the MySQL benchmark and a manual 10-record Fish import with --yes.';
    }

    public function fishStatus(): array
    {
        $collection = Collection::query()->where('slug', 'fish')->first();

        if ($collection === null) {
            return [
                'collection' => null,
                'imported_specimens' => 0,
                'incoming_files' => [],
                'incoming_file_count' => 0,
                'incoming_status' => 'Unknown',
            ];
        }

        return $this->collectionDetail($collection);
    }

    public function incomingFiles(Collection|string $collection): array
    {
        $paths = $this->locator->incomingFilePaths($collection);

        return array_map(function (string $path): array {
            $disk = Storage::disk((string) config('collection-imports.disk', 'local'));

            return [
                'name' => basename($path),
                'size' => $this->humanFileSize((int) $disk->size($path)),
                'modified_at' => $disk->lastModified($path),
            ];
        }, $paths);
    }

    public function report(): array
    {
        return [
            'application' => $this->application(),
            'collections' => $this->collections(),
            'fish' => $this->fishStatus(),
            'pipeline' => $this->pipelineCapabilities(),
            'workflow_steps' => $this->workflowSteps(),
            'next_recommended_step' => $this->nextRecommendedStep(),
        ];
    }

    protected function databaseDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    protected function databaseConnectionStatus(): string
    {
        try {
            DB::connection()->getPdo();

            return 'OK';
        } catch (Throwable) {
            return 'Unavailable';
        }
    }

    protected function incomingStatus(int $importedCount, int $incomingFileCount): string
    {
        if ($incomingFileCount > 0 && $importedCount === 0) {
            return 'Dataset detected but not yet committed to the portal database.';
        }

        if ($incomingFileCount > 0 && $importedCount > 0) {
            return 'Dataset detected and imported records are already present.';
        }

        if ($importedCount > 0) {
            return 'Imported records available.';
        }

        return 'No incoming dataset detected.';
    }

    protected function humanFileSize(int $bytes): string
    {
        if ($bytes >= 1_000_000_000) {
            return number_format($bytes / 1_000_000_000, 1).' GB';
        }

        if ($bytes >= 1_000_000) {
            return number_format($bytes / 1_000_000, 1).' MB';
        }

        if ($bytes >= 1_000) {
            return number_format($bytes / 1_000, 1).' KB';
        }

        return $bytes.' B';
    }
}
