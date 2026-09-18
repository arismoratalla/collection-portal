<?php

namespace Tests\Feature;

use App\Models\CollectingEvent;
use App\Models\Collection;
use App\Models\Determination;
use App\Models\Locality;
use App\Models\Preparation;
use App\Models\Specimen;
use App\Models\Taxon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalImportCommandTest extends TestCase
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

    public function test_controlled_sample_import_commits_ten_rows_and_is_idempotent(): void
    {
        $path = $this->writeSampleCsv();

        $this->artisan('portal:import', [
            'collection' => 'fish',
            '--limit' => 10,
            '--yes' => true,
        ])
            ->assertExitCode(0);

        $this->assertSame(10, Specimen::count());
        $this->assertSame(1, Taxon::count());
        $this->assertSame(10, Determination::count());
        $this->assertSame(1, CollectingEvent::count());
        $this->assertSame(1, Locality::count());
        $this->assertSame(10, Preparation::count());

        $firstSpecimen = Specimen::query()->orderBy('id')->firstOrFail();
        $this->assertSame('occ-1', $firstSpecimen->occurrence_id);
        $this->assertSame('000001', $firstSpecimen->getRawOriginal('catalog_number'));

        $this->artisan('portal:import', [
            'collection' => 'fish',
            '--limit' => 10,
            '--yes' => true,
        ])
            ->assertExitCode(0);

        $this->assertSame(10, Specimen::count());
        $this->assertSame(1, Taxon::count());
        $this->assertSame(10, Determination::count());
        $this->assertSame(1, CollectingEvent::count());
        $this->assertSame(1, Locality::count());
        $this->assertSame(10, Preparation::count());

        $this->assertTrue(Storage::disk('local')->exists($path));
    }

    public function test_controlled_sample_import_requires_confirmation(): void
    {
        $this->writeSampleCsv();

        $this->artisan('portal:import', [
            'collection' => 'fish',
            '--limit' => 10,
        ])
            ->expectsOutputToContain('Collection: Fish')
            ->expectsOutputToContain('Source file: fish-sample.csv')
            ->expectsOutputToContain('Requested rows: 10')
            ->expectsOutputToContain('Existing matching occurrences: 0')
            ->expectsOutputToContain('Expected creates: 10')
            ->expectsOutputToContain('Expected updates: 0')
            ->expectsOutputToContain('Re-run with --yes to confirm the committed sample import.')
            ->assertExitCode(1);

        $this->assertSame(0, Specimen::count());
    }

    public function test_controlled_sample_import_rejects_limits_above_one_hundred(): void
    {
        $this->writeSampleCsv();

        $this->artisan('portal:import', [
            'collection' => 'fish',
            '--limit' => 101,
            '--no-interaction' => true,
        ])
            ->expectsOutputToContain('Limit must not exceed 100 for committed sample imports.')
            ->assertExitCode(1);
    }

    public function test_controlled_sample_import_rejects_unknown_collections(): void
    {
        $this->artisan('portal:import', [
            'collection' => 'unknown',
            '--limit' => 10,
            '--no-interaction' => true,
        ])
            ->expectsOutputToContain('Unknown collection slug [unknown].')
            ->assertExitCode(1);
    }

    public function test_persist_preview_benchmark_reports_summary_metrics(): void
    {
        $this->writeSampleCsv();

        $this->artisan('portal:persist-preview', [
            'collection' => 'fish',
            '--limit' => 1,
            '--benchmark' => true,
        ])
            ->expectsOutputToContain('Persist preview limit: 1')
            ->expectsOutputToContain('Rolled back preview transaction.')
            ->expectsOutputToContain('Rows processed: 1')
            ->expectsOutputToContain('Benchmark mode:')
            ->expectsOutputToContain('SQL count:')
            ->expectsOutputToContain('Rows/sec:')
            ->assertExitCode(0);
    }

    public function test_inspect_import_reports_sample_records_and_totals(): void
    {
        $this->writeSampleCsv();

        $this->artisan('portal:import', [
            'collection' => 'fish',
            '--limit' => 10,
            '--yes' => true,
        ])
            ->assertExitCode(0);

        $this->artisan('portal:inspect-import', [
            'collection' => 'fish',
            '--limit' => 10,
        ])
            ->expectsOutputToContain('Collection: Fish')
            ->expectsOutputToContain('Sample size: 10')
            ->expectsOutputToContain('Catalog Number: 000001')
            ->expectsOutputToContain('Occurrence ID: occ-1')
            ->expectsOutputToContain('Taxon: Gadus morhua')
            ->expectsOutputToContain('Selected Range Counts')
            ->expectsOutputToContain('Specimens: 10')
            ->expectsOutputToContain('Taxa: 1')
            ->expectsOutputToContain('Determinations: 10')
            ->expectsOutputToContain('Geography: 4')
            ->expectsOutputToContain('Locality: 1')
            ->expectsOutputToContain('Collecting Event: 1')
            ->expectsOutputToContain('Preparation: 10')
            ->expectsOutputToContain('Data Quality')
            ->expectsOutputToContain('Duplicate occurrence IDs: 0')
            ->expectsOutputToContain('Duplicate catalog numbers: 0')
            ->expectsOutputToContain('Specimens without determination: 0')
            ->expectsOutputToContain('Specimens without event: 0')
            ->expectsOutputToContain('Specimens without preparation: 0')
            ->assertExitCode(0);
    }

    protected function writeSampleCsv(string $path = 'collection-imports/fish/incoming/fish-sample.csv'): string
    {
        $header = [
            'id',
            'catalogNumber',
            'modified',
            'scientificName',
            'scientificNameAuthorship',
            'taxonRank',
            'vernacularName',
            'continent',
            'country',
            'stateProvince',
            'county',
            'locality',
            'verbatimLocality',
            'waterBody',
            'verbatimDepth',
            'decimalLatitude',
            'decimalLongitude',
            'geodeticDatum',
            'coordinateUncertaintyInMeters',
            'footprintWKT',
            'georeferencedBy',
            'georeferencedDate',
            'georeferenceProtocol',
            'georeferenceSources',
            'georeferenceRemarks',
            'fieldNumber',
            'year',
            'month',
            'day',
            'verbatimEventDate',
            'samplingProtocol',
            'preparations',
            'identifiedBy',
        ];

        $rows = [];

        for ($index = 1; $index <= 10; $index++) {
            $rows[] = [
                'occ-'.$index,
                sprintf('%06d', $index),
                '2026-01-23 11:35:44',
                'Gadus morhua',
                'Linnaeus, 1758',
                'species',
                'Cod',
                'North America',
                'United States',
                'North Carolina',
                'Dare County',
                'Cape Hatteras',
                'Cape Hatteras, Dare County',
                'Pamlico Sound',
                '3 m',
                '35.1234567',
                '-75.1234567',
                'WGS84',
                '25.5',
                'POINT(-75.1234567 35.1234567)',
                'Jane Doe',
                '2026-08-20',
                'Gazetteer lookup',
                'NGA gazetteer',
                'Verified by hand',
                'FN-1',
                '1941',
                '5',
                '17',
                '17 May 1941',
                'Seine net',
                '3 EtOH70',
                'A. Researcher',
            ];
        }

        $csv = implode("\n", array_merge(
            [implode(',', $header)],
            array_map(function (array $row): string {
                return implode(',', array_map([$this, 'csvCell'], $row));
            }, $rows)
        ))."\n";

        Storage::disk('local')->put($path, $csv);

        return $path;
    }

    protected function csvCell(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
