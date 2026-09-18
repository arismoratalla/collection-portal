<?php

namespace App\Services\CollectionImports;

use App\Models\Collection;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CollectionImportFileLocator
{
    public function collectionSlug(Collection|string $collection): string
    {
        $slug = $collection instanceof Collection ? $collection->slug : $collection;

        $configured = config('collection-imports.collections', []);

        if (! array_key_exists($slug, $configured)) {
            throw new InvalidArgumentException("Unknown collection slug [{$slug}].");
        }

        return $configured[$slug];
    }

    public function collectionPath(Collection|string $collection): string
    {
        return $this->rootPath().'/'.$this->collectionSlug($collection);
    }

    public function incomingPath(Collection|string $collection): string
    {
        return $this->collectionPath($collection).'/incoming';
    }

    /**
     * @return array<int, string>
     */
    public function discover(Collection|string $collection): array
    {
        return array_map('basename', $this->incomingFilePaths($collection));
    }

    /**
     * @return array<int, string>
     */
    public function incomingFilePaths(Collection|string $collection): array
    {
        $disk = $this->disk();
        $incomingPath = $this->incomingPath($collection);

        if (! $disk->exists($incomingPath)) {
            return [];
        }

        $files = array_values(array_filter(
            $disk->files($incomingPath),
            fn (string $path): bool => $this->isCsvFile($path)
        ));

        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        return $files;
    }

    public function firstIncomingFilePath(Collection|string $collection): ?string
    {
        $files = $this->incomingFilePaths($collection);

        return $files[0] ?? null;
    }

    protected function disk(): Filesystem
    {
        return Storage::disk((string) config('collection-imports.disk', 'local'));
    }

    protected function rootPath(): string
    {
        return trim((string) config('collection-imports.root', 'collection-imports'), '/');
    }

    protected function isCsvFile(string $path): bool
    {
        $basename = basename($path);

        if (Str::startsWith($basename, '.')) {
            return false;
        }

        return Str::lower(pathinfo($basename, PATHINFO_EXTENSION)) === 'csv';
    }
}
