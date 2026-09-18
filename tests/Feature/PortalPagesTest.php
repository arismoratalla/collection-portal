<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Services\ProjectStatusService;
use Database\Seeders\CollectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CollectionSeeder::class);

        Storage::fake('local');
        config()->set('collection-imports.root', 'collection-imports');
    }

    public function test_home_page_renders_public_collection_cards(): void
    {
        $collections = Collection::query()->get()->keyBy('slug');
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeText('Research Collections Portal');
        $response->assertSeeText("Explore the Museum's research collections.");
        $response->assertSeeText('Birds');
        $response->assertSeeText('Fish');
        $response->assertSeeText('Herpetology');
        $response->assertSeeText('Mammals');
        $response->assertSeeText('Mollusk');
        $response->assertSeeText('Non-Mollusk');
        $response->assertSee(route('collections.search', $collections['birds']), false);
        $response->assertSee(route('collections.search', $collections['fish']), false);
        $response->assertSee(route('collections.search', $collections['herps']), false);
        $response->assertSee(route('collections.search', $collections['mammals']), false);
        $response->assertSee(route('collections.search', $collections['mollusk']), false);
        $response->assertSee(route('collections.search', $collections['non-mollusk']), false);
        $response->assertDontSeeText('storage/app/private');
    }

    public function test_collections_page_renders_all_six_public_cards(): void
    {
        $collections = Collection::query()->get()->keyBy('slug');
        $response = $this->get('/collections');

        $response->assertOk();
        $response->assertSeeText('Collections');
        $response->assertSeeText('Birds');
        $response->assertSeeText('Fish');
        $response->assertSeeText('Herpetology');
        $response->assertSeeText('Mammals');
        $response->assertSeeText('Mollusk');
        $response->assertSeeText('Non-Mollusk');
        $response->assertSee(route('collections.search', $collections['birds']), false);
        $response->assertSee(route('collections.search', $collections['fish']), false);
        $response->assertSee(route('collections.search', $collections['herps']), false);
        $response->assertSee(route('collections.search', $collections['mammals']), false);
        $response->assertSee(route('collections.search', $collections['mollusk']), false);
        $response->assertSee(route('collections.search', $collections['non-mollusk']), false);
        $response->assertDontSeeText('storage/app/private');
    }

    public function test_collection_show_route_uses_the_slug_and_shows_zero_imported_fish_records(): void
    {
        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();

        $response = $this->get(route('collections.show', $fish));

        $response->assertOk();
        $response->assertSeeText('Search this collection');
        $response->assertSeeText('Ichthyology Collection');
        $response->assertSeeText('Public specimen records are being prepared for this collection.');
        $response->assertDontSeeText('incoming dataset');
        $response->assertDontSeeText('incoming file count');
        $response->assertDontSeeText('slug');
        $response->assertDontSeeText('Active');
        $response->assertDontSeeText('ipt_occurrences_ichthyology_20260518.csv');
        $response->assertDontSee(base_path());
    }

    public function test_unknown_collection_returns_404(): void
    {
        $this->get('/collections/unknown-collection')->assertNotFound();
    }

    public function test_workflow_page_does_not_expose_a_committed_import_button(): void
    {
        $response = $this->get('/workflow');

        $response->assertOk();
        $response->assertSeeText('Research Collections Portal Workflow');
        $response->assertSeeText('php artisan portal:persist-preview fish --limit=1000 --benchmark');
        $response->assertSeeText('php artisan portal:import fish --limit=10 --yes');
        $response->assertDontSee('<button', false);
        $response->assertDontSee('type="submit"', false);
    }

    public function test_status_page_reports_real_collection_state(): void
    {
        Storage::disk('local')->put('collection-imports/fish/incoming/ipt_occurrences_ichthyology_20260518.csv', 'id,catalogNumber');

        $response = $this->get('/status');

        $response->assertOk();
        $response->assertSeeText('Project Status');
        $response->assertSeeText('Connection');
        $response->assertSeeText('Fish');
        $response->assertSeeText('Imported specimens: 0');
        $response->assertSeeText('ipt_occurrences_ichthyology_20260518.csv');
        $response->assertDontSee(base_path());
    }

    public function test_incoming_files_are_detected_via_the_status_service(): void
    {
        Storage::disk('local')->put('collection-imports/fish/incoming/ipt_occurrences_ichthyology_20260518.csv', 'id,catalogNumber');

        $fish = Collection::query()->where('slug', 'fish')->firstOrFail();
        $summary = app(ProjectStatusService::class)->collectionDetail($fish);

        $this->assertSame(1, $summary['incoming_file_count']);
        $this->assertSame('ipt_occurrences_ichthyology_20260518.csv', $summary['incoming_files'][0]['name']);
    }

    public function test_portal_status_command_outputs_project_status_summary(): void
    {
        $this->artisan('portal:status')
            ->expectsOutputToContain('Research Collections Portal')
            ->expectsOutputToContain('Database:')
            ->expectsOutputToContain('Connection: OK')
            ->expectsOutputToContain('Fish')
            ->expectsOutputToContain('Import Pipeline')
            ->expectsOutputToContain('Next Step')
            ->assertExitCode(0);

        $output = Artisan::output();

        $this->assertStringNotContainsString(base_path(), $output);
    }
}
