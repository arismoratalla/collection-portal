<?php

namespace App\Models;

use Database\Factories\GeographyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'parent_id',
    'name',
    'geography_type',
    'iso_code',
])]
class Geography extends Model
{
    /** @use HasFactory<GeographyFactory> */
    use HasFactory;

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function ancestorByType(string $geographyType): ?self
    {
        $node = $this;

        while ($node !== null) {
            if ($node->geography_type === $geographyType) {
                return $node;
            }

            $node = $node->relationLoaded('parent')
                ? $node->parent
                : $node->parent()->first();
        }

        return null;
    }
}
