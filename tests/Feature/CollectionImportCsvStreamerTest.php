<?php

namespace Tests\Feature;

use App\Services\Imports\CollectionImportCsvStreamer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollectionImportCsvStreamerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    public function test_streaming_handles_quoted_commas_and_embedded_newlines(): void
    {
        $path = $this->putCsv('collection-imports/fish/incoming/test.csv', <<<'CSV'
id,catalogNumber,scientificName,notes
occ-1,"CAT,001","Gadus, morhua","Line 1"
occ-2,CAT-002,"Species with
newline",""
CSV);

        $header = [];
        $rows = [];

        app(CollectionImportCsvStreamer::class)->process(
            $path,
            function (array $value) use (&$header): void {
                $header = $value;
            },
            function (array $row) use (&$rows): void {
                $rows[] = $row;
            }
        );

        $this->assertSame(['id', 'catalogNumber', 'scientificName', 'notes'], $header);
        $this->assertCount(2, $rows);
        $this->assertSame('CAT,001', $rows[0][1]);
        $this->assertSame("Species with\nnewline", $rows[1][2]);
    }

    public function test_streaming_handles_header_only_csv(): void
    {
        $path = $this->putCsv('collection-imports/fish/incoming/header-only.csv', "id,catalogNumber,modified\n");

        $header = [];
        $rows = [];

        app(CollectionImportCsvStreamer::class)->process(
            $path,
            function (array $value) use (&$header): void {
                $header = $value;
            },
            function (array $row) use (&$rows): void {
                $rows[] = $row;
            }
        );

        $this->assertSame(['id', 'catalogNumber', 'modified'], $header);
        $this->assertSame([], $rows);
    }

    public function test_streaming_handles_empty_csv(): void
    {
        $path = $this->putCsv('collection-imports/fish/incoming/empty.csv', '');

        $header = null;
        $rows = [];

        app(CollectionImportCsvStreamer::class)->process(
            $path,
            function (array $value) use (&$header): void {
                $header = $value;
            },
            function (array $row) use (&$rows): void {
                $rows[] = $row;
            }
        );

        $this->assertNull($header);
        $this->assertSame([], $rows);
    }

    protected function putCsv(string $path, string $contents): string
    {
        Storage::disk('local')->put($path, $contents);

        return Storage::disk('local')->path($path);
    }
}
