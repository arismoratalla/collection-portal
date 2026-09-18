<?php

namespace App\Services\Imports;

use App\Models\Collection;
use App\Services\CollectionImports\CollectionImportFileLocator;
use Illuminate\Support\Facades\Storage;

class CollectionImportRowProcessor
{
    public function __construct(
        protected CollectionImportFileLocator $locator,
        protected CollectionImportCsvStreamer $streamer,
        protected DarwinCoreOccurrenceMapper $mapper,
    ) {
        // Dependencies are injected by the container.
    }

    /**
     * Process incoming rows from the first CSV for a collection.
     *
     * @param  callable(array<string, mixed>, int, int, string, Collection): (bool|void)  $onMappedRow
     * @return array<string, mixed>
     */
    public function process(Collection|string $collection, int $limit, callable $onMappedRow): array
    {
        $collectionModel = $this->resolveCollection($collection);
        $path = $this->locator->firstIncomingFilePath($collectionModel);

        if ($path === null) {
            return [
                'collection' => $collectionModel,
                'path' => null,
                'file_name' => null,
                'rows_processed' => 0,
            ];
        }

        $disk = Storage::disk((string) config('collection-imports.disk', 'local'));
        $absolutePath = $disk->path($path);
        $rowsProcessed = 0;
        $headers = [];

        $this->streamer->process(
            $absolutePath,
            function (array $header) use (&$headers): void {
                $headers = $header;
            },
            function (array $row, int $lineNumber) use (
                &$rowsProcessed,
                $limit,
                $collectionModel,
                &$headers,
                $onMappedRow,
                $path
            ): bool {
                if ($rowsProcessed >= $limit) {
                    return false;
                }

                $rowsProcessed++;
                $mapped = $this->mapper->map($this->rowToAssociative($headers, $row), $collectionModel);

                return $onMappedRow($mapped, $rowsProcessed, $lineNumber, $path, $collectionModel) !== false;
            }
        );

        return [
            'collection' => $collectionModel,
            'path' => $path,
            'file_name' => basename($path),
            'rows_processed' => $rowsProcessed,
        ];
    }

    protected function resolveCollection(Collection|string $collection): Collection
    {
        if ($collection instanceof Collection) {
            return $collection;
        }

        $slug = $this->locator->collectionSlug($collection);

        return Collection::query()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|null>  $row
     * @return array<string, mixed>
     */
    protected function rowToAssociative(array $headers, array $row): array
    {
        $assoc = [];

        foreach ($headers as $index => $header) {
            $assoc[$header] = $row[$index] ?? null;
        }

        return $assoc;
    }
}
