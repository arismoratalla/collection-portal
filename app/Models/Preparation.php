<?php

namespace App\Models;

use Database\Factories\PreparationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'specimen_id',
    'preparation_type',
    'count',
    'storage_medium',
    'storage_location',
    'source_value',
    'remarks',
])]
class Preparation extends Model
{
    /** @use HasFactory<PreparationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'count' => 'integer',
        ];
    }

    public function specimen(): BelongsTo
    {
        return $this->belongsTo(Specimen::class);
    }
}
