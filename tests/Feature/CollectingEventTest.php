<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\CollectingEvent;
use App\Models\Locality;
use App\Models\Specimen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectingEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_collecting_event_can_have_multiple_collectors_and_order_is_preserved(): void
    {
        $event = CollectingEvent::factory()->create();
        $first = Agent::factory()->create(['full_name' => 'First Collector']);
        $second = Agent::factory()->create(['full_name' => 'Second Collector']);

        $event->collectors()->attach($first->id, ['sequence' => 2]);
        $event->collectors()->attach($second->id, ['sequence' => 1]);

        $collectorIds = $event->collectors()->pluck('agents.id')->all();

        $this->assertSame([$second->id, $first->id], $collectorIds);
        $this->assertTrue($first->collectingEvents->contains($event));
        $this->assertTrue($second->collectingEvents->contains($event));
    }

    public function test_multiple_specimens_attached_to_the_same_collecting_event_share_that_events_collectors(): void
    {
        $event = CollectingEvent::factory()->create();
        $collector = Agent::factory()->create();

        $event->collectors()->attach($collector->id, ['sequence' => 1]);

        $firstSpecimen = Specimen::factory()->create([
            'collecting_event_id' => $event->id,
        ]);
        $secondSpecimen = Specimen::factory()->create([
            'collecting_event_id' => $event->id,
        ]);

        $this->assertTrue($firstSpecimen->collectingEvent->is($event));
        $this->assertTrue($secondSpecimen->collectingEvent->is($event));
        $this->assertTrue($firstSpecimen->collectingEvent->collectors->contains($collector));
        $this->assertTrue($secondSpecimen->collectingEvent->collectors->contains($collector));
    }

    public function test_collecting_event_can_reference_locality(): void
    {
        $locality = Locality::factory()->create();
        $event = CollectingEvent::factory()->create([
            'locality_id' => $locality->id,
        ]);

        $this->assertTrue($event->locality->is($locality));
    }

    public function test_date_range_fields_work(): void
    {
        $event = CollectingEvent::factory()->create([
            'event_date' => '2026-08-20',
            'event_date_start' => '2026-08-18',
            'event_date_end' => '2026-08-20',
        ]);

        $this->assertSame('2026-08-20', $event->event_date->format('Y-m-d'));
        $this->assertSame('2026-08-18', $event->event_date_start->format('Y-m-d'));
        $this->assertSame('2026-08-20', $event->event_date_end->format('Y-m-d'));
    }

    public function test_year_only_collecting_event_is_preserved(): void
    {
        $event = CollectingEvent::factory()->create([
            'event_year' => 1941,
            'event_month' => null,
            'event_day' => null,
            'event_date' => null,
            'event_date_start' => null,
            'event_date_end' => null,
        ]);

        $this->assertSame(1941, $event->event_year);
        $this->assertNull($event->event_month);
        $this->assertNull($event->event_day);
        $this->assertNull($event->event_date);
    }

    public function test_year_and_month_collecting_event_is_preserved_without_inventing_a_day(): void
    {
        $event = CollectingEvent::factory()->create([
            'event_year' => 1941,
            'event_month' => 5,
            'event_day' => null,
            'event_date' => null,
            'event_date_start' => null,
            'event_date_end' => null,
        ]);

        $this->assertSame(1941, $event->event_year);
        $this->assertSame(5, $event->event_month);
        $this->assertNull($event->event_day);
        $this->assertNull($event->event_date);
    }

    public function test_full_collecting_event_date_can_be_stored(): void
    {
        $event = CollectingEvent::factory()->create([
            'event_year' => 1941,
            'event_month' => 5,
            'event_day' => 17,
            'event_date' => '1941-05-17',
            'event_date_start' => '1941-05-17',
            'event_date_end' => '1941-05-17',
        ]);

        $this->assertSame(1941, $event->event_year);
        $this->assertSame(5, $event->event_month);
        $this->assertSame(17, $event->event_day);
        $this->assertSame('1941-05-17', $event->event_date->format('Y-m-d'));
    }
}
