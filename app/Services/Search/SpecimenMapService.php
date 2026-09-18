<?php

namespace App\Services\Search;

use App\Models\Collection;

class SpecimenMapService
{
    public function __construct(protected SpecimenSearchQuery $search) {}

    /** @return array{markers: array<int, array<string, mixed>>, truncated: bool} */
    public function markers(Collection $collection, array $filters, ?array $bounds = null): array
    {
        $limit = (int) config('collection-search.map_marker_limit', 1000);
        $query = $this->search->filteredQuery($collection, $filters)
            ->join('collecting_events', 'collecting_events.id', '=', 'specimens.collecting_event_id')
            ->join('localities', 'localities.id', '=', 'collecting_events.locality_id')
            ->leftJoin('determinations', function ($join): void {
                $join->on('determinations.specimen_id', '=', 'specimens.id')->where('determinations.is_current', true);
            })
            ->leftJoin('taxa', 'taxa.id', '=', 'determinations.taxon_id')
            ->where('localities.coordinates_public', true)
            ->where('localities.sensitive', false)
            ->whereNotNull('localities.decimal_latitude')
            ->whereNotNull('localities.decimal_longitude')
            ->whereBetween('localities.decimal_latitude', [-90, 90])
            ->whereBetween('localities.decimal_longitude', [-180, 180]);

        if ($bounds !== null) {
            $query->whereBetween('localities.decimal_latitude', [$bounds['south'], $bounds['north']])
                ->whereBetween('localities.decimal_longitude', [$bounds['west'], $bounds['east']]);
        }

        $rows = $query
            ->select([
                'specimens.occurrence_id', 'specimens.catalog_number', 'specimens.scientific_name',
                'localities.locality', 'localities.verbatim_locality', 'localities.decimal_latitude', 'localities.decimal_longitude',
                'taxa.scientific_name as taxon_scientific_name',
            ])
            ->orderBy('specimens.id')
            ->limit($limit + 1)
            ->get();

        $truncated = $rows->count() > $limit;

        return [
            'markers' => $rows->take($limit)->map(fn ($row): array => [
                'occurrence_id' => $row->occurrence_id,
                'catalog_number' => $row->catalog_number,
                'scientific_name' => $row->scientific_name ?? $row->taxon_scientific_name,
                'locality' => $row->locality ?? $row->verbatim_locality,
                'collection' => $collection->name,
                'latitude' => (float) $row->decimal_latitude,
                'longitude' => (float) $row->decimal_longitude,
            ])->all(),
            'truncated' => $truncated,
        ];
    }
}
