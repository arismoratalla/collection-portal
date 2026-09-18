<?php

namespace App\Models;

use Database\Factories\CollectingEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'locality_id',
    'field_number',
    'event_year',
    'event_month',
    'event_day',
    'event_date',
    'event_date_start',
    'event_date_end',
    'verbatim_event_date',
    'sampling_protocol',
    'habitat',
    'field_notes',
])]
class CollectingEvent extends Model
{
    /** @use HasFactory<CollectingEventFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_year' => 'integer',
            'event_month' => 'integer',
            'event_day' => 'integer',
            'event_date' => 'date',
            'event_date_start' => 'date',
            'event_date_end' => 'date',
        ];
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function specimens(): HasMany
    {
        return $this->hasMany(Specimen::class);
    }

    public function collectors(): BelongsToMany
    {
        return $this->belongsToMany(Agent::class, 'collecting_event_collector')
            ->withPivot('sequence')
            ->withTimestamps()
            ->orderByPivot('sequence');
    }
}
