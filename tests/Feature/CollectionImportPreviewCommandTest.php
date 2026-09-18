<?php

namespace Tests\Feature;

use App\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollectionImportPreviewCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::disk('local')->deleteDirectory('collection-imports');
        config()->set('collection-imports.root', 'collection-imports');
        Collection::factory()->create([
            'name' => 'Fish',
            'slug' => 'fish',
        ]);
    }

    public function test_preview_command_honors_the_limit_and_keeps_catalog_numbers_as_strings(): void
    {
        Storage::disk('local')->put('collection-imports/fish/incoming/fish.csv', <<<'CSV'
id,catalogNumber,scientificName
occ-1,000123,Gadus morhua
occ-2,000124,Salmo salar
CSV);

        Artisan::call('portal:preview', [
            'collection' => 'fish',
            '--limit' => 1,
        ]);

        $output = Artisan::output();

        $this->assertStringContainsString('Preview limit: 1', $output);
        $this->assertStringContainsString('Row 1', $output);
        $this->assertStringContainsString('"catalog_number": "000123"', $output);
        $this->assertStringNotContainsString('Row 2', $output);
    }
}
