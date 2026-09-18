<?php

namespace App\Models;

use Database\Factories\DeterminationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'specimen_id',
    'taxon_id',
    'determiner_name',
    'determined_at',
    'remarks',
    'is_current',
])]
class Determination extends Model
{
    /** @use HasFactory<DeterminationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'determined_at' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function specimen(): BelongsTo
    {
        return $this->belongsTo(Specimen::class);
    }

    public function taxon(): BelongsTo
    {
        return $this->belongsTo(Taxon::class);
    }
}
