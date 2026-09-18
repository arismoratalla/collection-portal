<?php

namespace App\Models;

use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'first_name',
    'middle_name',
    'last_name',
    'full_name',
    'organization',
    'orcid',
])]
class Agent extends Model
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory;

    public function collectingEvents(): BelongsToMany
    {
        return $this->belongsToMany(CollectingEvent::class, 'collecting_event_collector')
            ->withPivot('sequence')
            ->withTimestamps()
            ->orderByPivot('sequence');
    }
}
