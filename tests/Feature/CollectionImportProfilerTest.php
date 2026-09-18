<?php

namespace Tests\Feature;

use App\Services\Imports\CollectionImportProfiler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollectionImportProfilerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config()->set('collection-imports.root', 'collection-imports');
    }

    public function test_profiler_reports_quality_counts_and_mapping_summary(): void
    {
        $path = $this->putCsv('collection-imports/fish/incoming/profiler.csv', <<<'CSV'
id,catalogNumber,modified,scientificName,decimalLatitude,decimalLongitude,individualCount,year,month,day,license,extraField
occ-1,CAT-1,2026-08-20,"Gadus, morhua",12.34,-56.78,3,2026,8,20,CC-BY,foo
occ-1,CAT-1,,Bacalao,12.34,,abc,2026,13,40,CC-BY,bar
,CAT-3,2026-08-22,,,181.1,2,2026,12,15,CC-BY,baz
CSV);

        $report = app(CollectionImportProfiler::class)->profileFilePath('fish', 'collection-imports/fish/incoming/profiler.csv');

        $this->assertSame('fish', $report['collection_slug']);
        $this->assertSame('Fish', $report['collection_name']);
        $this->assertSame('profiler.csv', $report['filename']);
        $this->assertSame(3, $report['total_rows']);
        $this->assertSame(0, $report['blank_rows']);
        $this->assertSame(0, $report['malformed_rows']);
        $this->assertSame(12, $report['column_count']);
        $this->assertSame(['id', 'catalogNumber', 'modified', 'scientificName', 'decimalLatitude', 'decimalLongitude', 'individualCount', 'year', 'month', 'day', 'license', 'extraField'], $report['headers']);

        $this->assertSame(1, $report['identifiers']['id']['missing']);
        $this->assertSame(1, $report['identifiers']['id']['duplicates']);
        $this->assertSame(0, $report['identifiers']['catalogNumber']['missing']);
        $this->assertSame(1, $report['identifiers']['catalogNumber']['duplicates']);
        $this->assertSame(1, $report['identifiers']['modified']['missing']);

        $this->assertSame(1, $report['quality']['missing_occurrence_ids']);
        $this->assertSame(1, $report['quality']['duplicate_occurrence_ids']);
        $this->assertSame(0, $report['quality']['missing_catalog_numbers']);
        $this->assertSame(1, $report['quality']['duplicate_catalog_numbers']);
        $this->assertSame(1, $report['quality']['latitude_only']);
        $this->assertSame(1, $report['quality']['longitude_only']);
        $this->assertSame(1, $report['quality']['invalid_individual_count']);
        $this->assertSame(1, $report['quality']['date_inconsistencies']);

        $this->assertSame(4, $report['mapping']['counts']['direct']);
        $this->assertSame(6, $report['mapping']['counts']['domain']);
        $this->assertSame(1, $report['mapping']['counts']['metadata']);
        $this->assertSame(1, $report['mapping']['counts']['unmapped']);
        $this->assertContains('id', $report['mapping']['direct']);
        $this->assertContains('scientificName', $report['mapping']['domain']);
        $this->assertContains('license', $report['mapping']['metadata']);
        $this->assertContains('extraField', $report['mapping']['unmapped']);

        $this->assertSame('occ-1', $report['columns'][0]['samples'][0]);
        $this->assertSame('CAT-1', $report['columns'][1]['samples'][0]);
    }

    public function test_profiler_handles_missing_optional_columns_and_empty_directory(): void
    {
        $this->putCsv('collection-imports/mammals/incoming/minimal.csv', <<<'CSV'
id,catalogNumber
occ-1,CAT-1
CSV);

        $report = app(CollectionImportProfiler::class)->profileFilePath('mammals', 'collection-imports/mammals/incoming/minimal.csv');

        $this->assertSame(1, $report['total_rows']);
        $this->assertSame(2, $report['column_count']);
        $this->assertSame([], $report['quality']['empty_fields']);

        $empty = app(CollectionImportProfiler::class)->profileCollection('birds');

        $this->assertSame([], $empty);
    }

    protected function putCsv(string $path, string $contents): string
    {
        Storage::disk('local')->put($path, $contents);

        return Storage::disk('local')->path($path);
    }
}
