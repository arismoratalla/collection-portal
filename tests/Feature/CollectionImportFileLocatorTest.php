<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Services\CollectionImports\CollectionImportFileLocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class CollectionImportFileLocatorTest extends TestCase
{
    use RefreshDatabase;

    protected string $importRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importRoot = 'collection-imports-test-'.uniqid();

        config()->set('collection-imports.root', $this->importRoot);
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->deleteDirectory($this->importRoot);

        parent::tearDown();
    }

    public function test_path_resolution_uses_the_configured_root_and_collection_slug(): void
    {
        $collection = Collection::factory()->create([
            'slug' => 'fish',
        ]);

        $locator = app(CollectionImportFileLocator::class);

        $this->assertSame($this->importRoot.'/fish', $locator->collectionPath($collection));
        $this->assertSame($this->importRoot.'/fish/incoming', $locator->incomingPath('fish'));
    }

    public function test_fish_discovery_finds_csv_files_in_incoming_only(): void
    {
        $this->writeFile('fish/incoming/ipt_occurrences_ichthyology_20260518.csv');
        $this->writeFile('fish/incoming/ipt_occurrences_ichthyology_20260519.csv');
        $this->writeFile('fish/incoming/readme.txt');
        $this->writeFile('fish/incoming/.hidden.csv');
        $this->writeFile('fish/archive/archived.csv');

        $locator = app(CollectionImportFileLocator::class);

        $this->assertSame([
            'ipt_occurrences_ichthyology_20260518.csv',
            'ipt_occurrences_ichthyology_20260519.csv',
        ], $locator->discover('fish'));
    }

    public function test_ignores_non_csv_files_and_hidden_files(): void
    {
        $this->writeFile('fish/incoming/.DS_Store');
        $this->writeFile('fish/incoming/.hidden.csv');
        $this->writeFile('fish/incoming/notes.txt');
        $this->writeFile('fish/incoming/data.csv');

        $locator = app(CollectionImportFileLocator::class);

        $this->assertSame(['data.csv'], $locator->discover('fish'));
    }

    public function test_results_are_returned_in_deterministic_filename_order(): void
    {
        $this->writeFile('fish/incoming/zeta.csv');
        $this->writeFile('fish/incoming/alpha.csv');
        $this->writeFile('fish/incoming/MIDDLE.csv');

        $locator = app(CollectionImportFileLocator::class);

        $this->assertSame([
            'alpha.csv',
            'MIDDLE.csv',
            'zeta.csv',
        ], $locator->discover('fish'));
    }

    public function test_collection_folders_are_isolated_from_each_other(): void
    {
        $this->writeFile('fish/incoming/fish.csv');
        $this->writeFile('birds/incoming/birds.csv');

        $locator = app(CollectionImportFileLocator::class);

        $this->assertSame(['fish.csv'], $locator->discover('fish'));
        $this->assertSame(['birds.csv'], $locator->discover('birds'));
    }

    public function test_unknown_slug_is_rejected(): void
    {
        $locator = app(CollectionImportFileLocator::class);

        $this->expectException(InvalidArgumentException::class);

        $locator->discover('unknown');
    }

    public function test_empty_folder_returns_an_empty_list(): void
    {
        $locator = app(CollectionImportFileLocator::class);

        $this->assertSame([], $locator->discover('mollusk'));
    }

    public function test_portal_imports_command_lists_grouped_files_and_supports_specific_collection(): void
    {
        $fish = Collection::factory()->create([
            'name' => 'Fish',
            'slug' => 'fish',
        ]);
        Collection::factory()->create([
            'name' => 'Birds',
            'slug' => 'birds',
        ]);

        $this->writeFile('fish/incoming/ipt_occurrences_ichthyology_20260518.csv');
        $this->writeFile('birds/incoming/birds.csv');

        $this->artisan('portal:imports')
            ->expectsOutputToContain('Fish')
            ->expectsOutputToContain('ipt_occurrences_ichthyology_20260518.csv')
            ->expectsOutputToContain('Birds')
            ->expectsOutputToContain('birds.csv')
            ->assertExitCode(0);

        $this->artisan('portal:imports', ['collection' => 'fish'])
            ->expectsOutputToContain('Fish')
            ->expectsOutputToContain('ipt_occurrences_ichthyology_20260518.csv')
            ->assertExitCode(0);
    }

    protected function writeFile(string $path, string $contents = 'id,name'): void
    {
        Storage::disk('local')->put($this->importRoot.'/'.$path, $contents);
    }
}
