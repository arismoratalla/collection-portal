<?php

namespace App\Models;

use Database\Factories\SpecimenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'collection_id',
    'collecting_event_id',
    'occurrence_id',
    'catalog_number',
    'scientific_name',
    'basis_of_record',
    'type_status',
    'individual_count',
    'source_modified_at',
])]
class Specimen extends Model
{
    /** @use HasFactory<SpecimenFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'individual_count' => 'integer',
            'source_modified_at' => 'datetime',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function collectingEvent(): BelongsTo
    {
        return $this->belongsTo(CollectingEvent::class);
    }

    public function determinations(): HasMany
    {
        return $this->hasMany(Determination::class);
    }

    public function currentDetermination(): HasOne
    {
        return $this->hasOne(Determination::class)->where('is_current', true);
    }

    public function preparations(): HasMany
    {
        return $this->hasMany(Preparation::class);
    }
}
