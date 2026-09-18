<?php

namespace Tests\Feature;

use App\Models\Taxon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxonTest extends TestCase
{
    use RefreshDatabase;

    public function test_taxon_supports_parent_and_children_relationships(): void
    {
        $parent = Taxon::factory()->create([
            'scientific_name' => 'Animalia',
            'rank' => 'kingdom',
        ]);

        $child = Taxon::factory()->create([
            'parent_id' => $parent->id,
            'scientific_name' => 'Chordata',
            'rank' => 'phylum',
        ]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($parent->children->contains($child));
    }

    public function test_taxon_factory_can_create_a_taxon(): void
    {
        $taxon = Taxon::factory()->create();

        $this->assertDatabaseHas('taxa', [
            'id' => $taxon->id,
            'scientific_name' => $taxon->scientific_name,
        ]);
        $this->assertTrue($taxon->is_accepted);
    }

    public function test_taxon_can_store_a_vernacular_name(): void
    {
        $taxon = Taxon::factory()->create([
            'scientific_name' => 'Mugil cephalus',
            'vernacular_name' => 'Striped mullet',
        ]);

        $this->assertSame('Striped mullet', $taxon->vernacular_name);
        $this->assertDatabaseHas('taxa', [
            'id' => $taxon->id,
            'vernacular_name' => 'Striped mullet',
        ]);
    }

    public function test_taxon_source_and_identifier_are_unique_together(): void
    {
        Taxon::factory()->create([
            'source' => 'specify',
            'source_identifier' => 'ABC-123',
        ]);

        $this->expectException(QueryException::class);

        Taxon::factory()->create([
            'source' => 'specify',
            'source_identifier' => 'ABC-123',
        ]);
    }
}
