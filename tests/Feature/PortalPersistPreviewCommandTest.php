<?php

namespace Tests\Feature;

use App\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalPersistPreviewCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config()->set('collection-imports.root', 'collection-imports');
    }

    public function test_persist_preview_command_streams_rows_and_rolls_back_all_changes(): void
    {
        Collection::factory()->create(['slug' => 'fish']);

        Storage::disk('local')->put('collection-imports/fish/incoming/persist-preview.csv', <<<'CSV'
id,catalogNumber,modified,scientificName,scientificNameAuthorship,taxonRank,vernacularName,continent,country,stateProvince,county,locality,verbatimLocality,waterBody,verbatimDepth,decimalLatitude,decimalLongitude,geodeticDatum,coordinateUncertaintyInMeters,footprintWKT,georeferencedBy,georeferencedDate,georeferenceProtocol,georeferenceSources,georeferenceRemarks,fieldNumber,year,month,day,verbatimEventDate,samplingProtocol,preparations,identifiedBy
occ-1,000123,2026-01-23 11:35:44,Gadus morhua,"Linnaeus, 1758",species,Cod,North America,United States,North Carolina,Dare County,Cape Hatteras,"Cape Hatteras, Dare County",Pamlico Sound,3 m,35.1234567,-75.1234567,WGS84,25.5,"POINT(-75.1234567 35.1234567)",Jane Doe,2026-08-20,Gazetteer lookup,"NGA gazetteer","Verified by hand",FN-1,1941,5,17,17 May 1941,Seine net,3 EtOH70,A. Researcher
CSV);

        Artisan::call('portal:persist-preview', [
            'collection' => 'fish',
            '--limit' => 1,
        ]);

        $output = Artisan::output();

        $this->assertStringContainsString('Persist preview limit: 1', $output);
        $this->assertStringContainsString('Row 1 | specimen occ-1 | created', $output);
        $this->assertStringContainsString('Rolled back preview transaction.', $output);
        $this->assertSame(0, Collection::query()->where('slug', 'fish')->firstOrFail()->specimens()->count());
    }
}
