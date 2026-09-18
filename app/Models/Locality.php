<?php

namespace App\Models;

use Database\Factories\LocalityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'geography_id',
    'verbatim_locality',
    'locality',
    'decimal_latitude',
    'decimal_longitude',
    'coordinate_uncertainty_meters',
    'geodetic_datum',
    'georeference_sources',
    'georeference_remarks',
    'water_body',
    'verbatim_depth',
    'footprint_wkt',
    'georeferenced_by',
    'georeferenced_date',
    'georeference_protocol',
    'coordinates_public',
    'sensitive',
])]
class Locality extends Model
{
    /** @use HasFactory<LocalityFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'coordinates_public' => 'boolean',
            'sensitive' => 'boolean',
            'georeferenced_date' => 'date',
        ];
    }

    public function geography(): BelongsTo
    {
        return $this->belongsTo(Geography::class);
    }

    public function toArray(): array
    {
        $attributes = parent::toArray();

        if (! $this->coordinates_public || $this->sensitive) {
            unset(
                $attributes['decimal_latitude'],
                $attributes['decimal_longitude'],
                $attributes['coordinate_uncertainty_meters'],
                $attributes['geodetic_datum'],
                $attributes['georeference_sources'],
                $attributes['georeference_remarks'],
                $attributes['water_body'],
                $attributes['verbatim_depth'],
                $attributes['footprint_wkt'],
                $attributes['georeferenced_by'],
                $attributes['georeferenced_date'],
                $attributes['georeference_protocol']
            );
        }

        return $attributes;
    }
}
