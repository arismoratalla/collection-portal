<?php

namespace App\Models;

use Database\Factories\TaxonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'parent_id',
    'scientific_name',
    'canonical_name',
    'authorship',
    'rank',
    'kingdom',
    'phylum',
    'class_name',
    'order_name',
    'family',
    'genus',
    'specific_epithet',
    'infraspecific_epithet',
    'vernacular_name',
    'source',
    'source_identifier',
    'is_accepted',
])]
class Taxon extends Model
{
    /** @use HasFactory<TaxonFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_accepted' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
