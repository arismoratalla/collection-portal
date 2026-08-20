<?php

namespace Tests\Feature;

use App\Models\Collection as CollectionModel;
use Database\Seeders\CollectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_six_collections_are_seeded(): void
    {
        $this->seed(CollectionSeeder::class);

        $this->assertDatabaseCount('collections', 6);

        $this->assertDatabaseHas('collections', ['name' => 'Fish', 'slug' => 'fish']);
        $this->assertDatabaseHas('collections', ['name' => 'Mollusk', 'slug' => 'mollusk']);
        $this->assertDatabaseHas('collections', ['name' => 'Non-Mollusk', 'slug' => 'non-mollusk']);
        $this->assertDatabaseHas('collections', ['name' => 'Herpetology', 'slug' => 'herps']);
        $this->assertDatabaseHas('collections', ['name' => 'Mammals', 'slug' => 'mammals']);
        $this->assertDatabaseHas('collections', ['name' => 'Birds', 'slug' => 'birds']);
    }

    public function test_collection_slugs_are_unique(): void
    {
        $this->seed(CollectionSeeder::class);

        $slugs = CollectionModel::query()->pluck('slug');

        $this->assertCount(6, $slugs);
        $this->assertCount(6, $slugs->unique());
    }

    public function test_seeded_collections_are_active(): void
    {
        $this->seed(CollectionSeeder::class);

        $this->assertTrue(CollectionModel::query()->where('slug', 'fish')->value('is_active'));
        $this->assertTrue(CollectionModel::query()->where('slug', 'mollusk')->value('is_active'));
        $this->assertTrue(CollectionModel::query()->where('slug', 'non-mollusk')->value('is_active'));
        $this->assertTrue(CollectionModel::query()->where('slug', 'herps')->value('is_active'));
        $this->assertTrue(CollectionModel::query()->where('slug', 'mammals')->value('is_active'));
        $this->assertTrue(CollectionModel::query()->where('slug', 'birds')->value('is_active'));
    }

    public function test_collection_factory_can_create_a_collection(): void
    {
        $collection = CollectionModel::factory()->create();

        $this->assertDatabaseCount('collections', 1);
        $this->assertNotEmpty($collection->name);
        $this->assertNotEmpty($collection->slug);
        $this->assertTrue($collection->is_active);
    }
}
