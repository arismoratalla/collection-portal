<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\CollectingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_can_retrieve_its_collecting_events(): void
    {
        $agent = Agent::factory()->create();
        $firstEvent = CollectingEvent::factory()->create();
        $secondEvent = CollectingEvent::factory()->create();

        $agent->collectingEvents()->attach($firstEvent->id, ['sequence' => 2]);
        $agent->collectingEvents()->attach($secondEvent->id, ['sequence' => 1]);

        $collectingEventIds = $agent->collectingEvents()->pluck('collecting_events.id')->all();

        $this->assertSame([$secondEvent->id, $firstEvent->id], $collectingEventIds);
        $this->assertTrue($firstEvent->collectors->contains($agent));
        $this->assertTrue($secondEvent->collectors->contains($agent));
    }

    public function test_agent_factory_can_create_an_agent(): void
    {
        $agent = Agent::factory()->create();

        $this->assertDatabaseHas('agents', [
            'id' => $agent->id,
            'full_name' => $agent->full_name,
        ]);
    }
}
