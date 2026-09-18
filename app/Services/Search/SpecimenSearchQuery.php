<?php

namespace App\Services\Search;

use App\Models\CollectingEvent;
use App\Models\Collection;
use App\Models\Specimen;
use App\Models\Taxon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SpecimenSearchQuery
{
    public function search(Collection $collection, array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $query = $this->filteredQuery($collection, $filters)->with([
            'collection',
            'collectingEvent.locality.geography.parent.parent.parent',
            'currentDetermination.taxon',
            'preparations',
        ]);

        $sort = is_string($filters['sort'] ?? null) ? $filters['sort'] : 'catalog_number';
        $direction = is_string($filters['direction'] ?? null) ? $filters['direction'] : 'asc';
        $this->applySort($query, $sort, $direction);

        return $query->paginate($perPage)->withQueryString();
    }

    public function filteredQuery(Collection $collection, array $filters): Builder
    {
        $query = Specimen::query()->where('specimens.collection_id', $collection->id);
        $this->applyFilters($query, $filters);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    public function applyFilters(Builder $query, array $filters): void
    {
        $this->applyLike($query, $filters['catalog_number'] ?? null, fn (Builder $q, string $value) => $q->where('specimens.catalog_number', 'like', $this->like($value)));
        $this->applyLike($query, $filters['field_number'] ?? null, fn (Builder $q, string $value) => $q->whereHas('collectingEvent', fn (Builder $event) => $event->where('field_number', 'like', $this->like($value))));

        foreach (['family', 'genus', 'specific_epithet', 'infraspecific_epithet', 'scientific_name', 'vernacular_name'] as $facet) {
            $this->applyMultiValue($query, $filters[$facet] ?? null, function (Builder $q, array $values) use ($facet): void {
                $q->whereHas('currentDetermination.taxon', fn (Builder $taxon) => $this->whereLikeAny($taxon, $facet, $values));
            });
        }

        $this->applyMultiValue($query, $filters['type_status'] ?? null, fn (Builder $q, array $values) => $this->whereLikeAny($q, 'specimens.type_status', $values));
        $this->applyMultiValue($query, $filters['identified_by'] ?? null, fn (Builder $q, array $values) => $q->whereHas('currentDetermination', fn (Builder $determination) => $this->whereLikeAny($determination, 'determiner_name', $values)));
        $this->applyMultiValue($query, $filters['preparation'] ?? null, fn (Builder $q, array $values) => $q->whereHas('preparations', fn (Builder $preparation) => $this->whereLikeAny($preparation, 'source_value', $values)));

        foreach (['locality', 'water_body', 'verbatim_depth'] as $facet) {
            $this->applyMultiValue($query, $filters[$facet] ?? null, function (Builder $q, array $values) use ($facet): void {
                $q->whereHas('collectingEvent.locality', fn (Builder $locality) => $this->whereLikeAny($locality, $facet, $values));
            });
        }

        foreach (['event_year', 'event_month', 'event_day', 'verbatim_event_date', 'sampling_protocol'] as $facet) {
            $this->applyMultiValue($query, $filters[$facet] ?? null, function (Builder $q, array $values) use ($facet): void {
                $q->whereHas('collectingEvent', fn (Builder $event) => $this->whereLikeAny($event, $facet, $values));
            });
        }

        foreach (['continent', 'country', 'state', 'county'] as $type) {
            $this->applyMultiValue($query, $filters[$type] ?? null, fn (Builder $q, array $values) => $this->applyGeographyFilter($q, $type, $values));
        }

        foreach (['year_from' => '>=', 'year_to' => '<='] as $key => $operator) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->whereHas('collectingEvent', fn (Builder $event) => $event->where('event_year', $operator, (int) $filters[$key]));
            }
        }

        $this->applyCoordinateRange($query, 'latitude_min', 'decimal_latitude', '>=', $filters);
        $this->applyCoordinateRange($query, 'latitude_max', 'decimal_latitude', '<=', $filters);
        $this->applyCoordinateRange($query, 'longitude_min', 'decimal_longitude', '>=', $filters);
        $this->applyCoordinateRange($query, 'longitude_max', 'decimal_longitude', '<=', $filters);
        $this->applyGlobalSearch($query, $filters['q'] ?? $filters['quick_search'] ?? null);
    }

    public function applySort(Builder $query, string $sort, string $direction): void
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        if ($sort === 'event_year') {
            $query->orderBy(
                CollectingEvent::query()->select('event_year')->whereColumn('collecting_events.id', 'specimens.collecting_event_id'),
                $direction,
            );
        } elseif (in_array($sort, ['family', 'genus', 'specific_epithet'], true)) {
            $query->orderBy(
                Taxon::query()->select($sort)->join('determinations', 'determinations.taxon_id', '=', 'taxa.id')
                    ->whereColumn('determinations.specimen_id', 'specimens.id')->where('determinations.is_current', true),
                $direction,
            );
        } else {
            $query->orderBy('specimens.catalog_number', $direction);
        }

        $query->orderBy('specimens.catalog_number');
    }

    protected function applyCoordinateRange(Builder $query, string $key, string $column, string $operator, array $filters): void
    {
        if (! isset($filters[$key]) || $filters[$key] === '') {
            return;
        }

        $query->whereHas('collectingEvent.locality', function (Builder $locality) use ($column, $operator, $filters, $key): void {
            $locality->where('coordinates_public', true)->where('sensitive', false)->where($column, $operator, (float) $filters[$key]);
        });
    }

    protected function applyGlobalSearch(Builder $query, mixed $term): void
    {
        if (! is_string($term) || trim($term) === '') {
            return;
        }

        $term = trim($term);
        $query->where(function (Builder $q) use ($term): void {
            $q->where('specimens.catalog_number', 'like', $this->like($term))
                ->orWhere('specimens.scientific_name', 'like', $this->like($term))
                ->orWhereHas('currentDetermination.taxon', function (Builder $taxon) use ($term): void {
                    $taxon->where(function (Builder $inner) use ($term): void {
                        foreach (['scientific_name', 'family', 'genus', 'specific_epithet', 'infraspecific_epithet', 'vernacular_name'] as $column) {
                            $inner->orWhere($column, 'like', $this->like($term));
                        }
                    });
                })
                ->orWhereHas('collectingEvent', function (Builder $event) use ($term): void {
                    $event->where('field_number', 'like', $this->like($term))
                        ->orWhereHas('locality', function (Builder $locality) use ($term): void {
                            $locality->where('locality', 'like', $this->like($term))
                                ->orWhere('verbatim_locality', 'like', $this->like($term));
                        });
                });

            $this->applyGlobalGeographySearch($q, $term);
        });
    }

    protected function applyMultiValue(Builder $query, mixed $input, callable $callback): void
    {
        $values = collect(is_array($input) ? $input : [$input])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))->unique()->values()->all();

        if ($values !== []) {
            $callback($query, $values);
        }
    }

    protected function applyLike(Builder $query, mixed $input, callable $callback): void
    {
        if (is_string($input) && trim($input) !== '') {
            $callback($query, trim($input));
        }
    }

    /** @param array<int, string> $values */
    protected function whereLikeAny($query, string $column, array $values): void
    {
        $query->where(function (Builder $branch) use ($column, $values): void {
            foreach ($values as $value) {
                $branch->orWhere($column, 'like', $this->like($value));
            }
        });
    }

    /** @param array<int, string> $values */
    protected function applyGeographyFilter(Builder $query, string $type, array $values): void
    {
        $query->whereIn('specimens.collecting_event_id', function ($events) use ($type, $values): void {
            $events->from('collecting_events')
                ->join('localities', 'localities.id', '=', 'collecting_events.locality_id')
                ->join('geographies as geography_0', 'geography_0.id', '=', 'localities.geography_id')
                ->leftJoin('geographies as geography_1', 'geography_1.id', '=', 'geography_0.parent_id')
                ->leftJoin('geographies as geography_2', 'geography_2.id', '=', 'geography_1.parent_id')
                ->leftJoin('geographies as geography_3', 'geography_3.id', '=', 'geography_2.parent_id')
                ->select('collecting_events.id')
                ->where(function ($levels) use ($type, $values): void {
                    foreach (['geography_0', 'geography_1', 'geography_2', 'geography_3'] as $level) {
                        $levels->orWhere(function ($match) use ($level, $type, $values): void {
                            $match->where("{$level}.geography_type", $type)
                                ->where(function ($names) use ($level, $values): void {
                                    foreach ($values as $value) {
                                        $names->orWhere("{$level}.name", 'like', $this->like($value));
                                    }
                                });
                        });
                    }
                });
        });
    }

    protected function applyGlobalGeographySearch(Builder $query, string $term): void
    {
        $query->orWhereIn('specimens.collecting_event_id', function ($events) use ($term): void {
            $events->from('collecting_events')
                ->join('localities', 'localities.id', '=', 'collecting_events.locality_id')
                ->join('geographies as geography_0', 'geography_0.id', '=', 'localities.geography_id')
                ->leftJoin('geographies as geography_1', 'geography_1.id', '=', 'geography_0.parent_id')
                ->leftJoin('geographies as geography_2', 'geography_2.id', '=', 'geography_1.parent_id')
                ->leftJoin('geographies as geography_3', 'geography_3.id', '=', 'geography_2.parent_id')
                ->select('collecting_events.id')
                ->where(function ($names) use ($term): void {
                    foreach (['geography_0', 'geography_1', 'geography_2', 'geography_3'] as $level) {
                        $names->orWhere("{$level}.name", 'like', $this->like($term));
                    }
                });
        });
    }

    protected function like(string $value): string
    {
        return '%'.Str::of($value)->replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'])->toString().'%';
    }
}
