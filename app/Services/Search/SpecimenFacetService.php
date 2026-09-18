<?php

namespace App\Services\Search;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SpecimenFacetService
{
    public function __construct(protected SpecimenSearchQuery $search) {}

    /** @return array<string, mixed> */
    public function facet(Collection $collection, string $facet, array $filters, ?string $term = null, int $page = 1, ?int $perPage = null): array
    {
        $definition = $this->definition($collection, $facet);
        $perPage ??= (int) config('collection-search.facet_load_increment', 20);
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        // Conventional faceting: every other active filter applies, while this
        // facet's own selections are excluded so remaining choices stay useful.
        unset($filters[$facet]);
        $query = $this->search->filteredQuery($collection, $filters);
        $options = $this->optionsQuery($query, $definition);

        if ($term !== null && trim($term) !== '') {
            $options->havingRaw('value LIKE ?', ['%'.addcslashes(trim($term), '\\%_').'%']);
        }

        $total = DB::query()->fromSub(clone $options, 'facet_options')->count();
        $values = $options
            ->orderByDesc('count')
            ->orderBy('value')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (object $row): array => ['value' => (string) $row->value, 'count' => (int) $row->count])
            ->values()
            ->all();

        return [
            'key' => $facet,
            'label' => $definition['label'],
            'values' => $values,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => $page * $perPage < $total,
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function initial(Collection $collection, array $filters): array
    {
        $facets = collect(config('collection-search.initial_facets', []))
            ->merge(collect($filters)->filter(fn (mixed $value): bool => is_array($value) && $value !== [])->keys())
            ->intersect($this->facetsFor($collection));

        return $facets
            ->mapWithKeys(fn (string $facet): array => [$facet => $this->facet(
                $collection,
                $facet,
                $filters,
                null,
                1,
                (int) config('collection-search.facet_initial_limit', 5),
            )])
            ->all();
    }

    /** @return array<int, string> */
    public function facetsFor(Collection $collection): array
    {
        return config("collection-search.collections.{$collection->slug}.facets", config('collection-search.default.facets', []));
    }

    /** @return array<string, mixed> */
    public function definition(Collection $collection, string $facet): array
    {
        if (! in_array($facet, $this->facetsFor($collection), true)) {
            throw new InvalidArgumentException("Unsupported facet [{$facet}].");
        }

        $definition = config("collection-search.facet_definitions.{$facet}");

        if (! is_array($definition)) {
            throw new InvalidArgumentException("Unsupported facet [{$facet}].");
        }

        return $definition;
    }

    protected function optionsQuery(Builder $query, array $definition)
    {
        $base = $query->select('specimens.id');

        if (isset($definition['geography_type'])) {
            return DB::query()
                ->fromSub($base, 'matching_specimens')
                ->join('specimens', 'specimens.id', '=', 'matching_specimens.id')
                ->join('collecting_events', 'collecting_events.id', '=', 'specimens.collecting_event_id')
                ->join('localities', 'localities.id', '=', 'collecting_events.locality_id')
                ->join('geographies as geography_0', 'geography_0.id', '=', 'localities.geography_id')
                ->leftJoin('geographies as geography_1', 'geography_1.id', '=', 'geography_0.parent_id')
                ->leftJoin('geographies as geography_2', 'geography_2.id', '=', 'geography_1.parent_id')
                ->leftJoin('geographies as geography_3', 'geography_3.id', '=', 'geography_2.parent_id')
                ->selectRaw('COALESCE(CASE WHEN geography_0.geography_type = ? THEN geography_0.name END, CASE WHEN geography_1.geography_type = ? THEN geography_1.name END, CASE WHEN geography_2.geography_type = ? THEN geography_2.name END, CASE WHEN geography_3.geography_type = ? THEN geography_3.name END) as value, COUNT(DISTINCT specimens.id) as count', [$definition['geography_type'], $definition['geography_type'], $definition['geography_type'], $definition['geography_type']])
                ->where(function ($builder) use ($definition): void {
                    $builder->where('geography_0.geography_type', $definition['geography_type'])
                        ->orWhere('geography_1.geography_type', $definition['geography_type'])
                        ->orWhere('geography_2.geography_type', $definition['geography_type'])
                        ->orWhere('geography_3.geography_type', $definition['geography_type']);
                })
                ->groupBy('value');
        }

        $joins = [
            'taxon' => fn ($builder) => $builder->join('determinations', function ($join): void {
                $join->on('determinations.specimen_id', '=', 'specimens.id')->where('determinations.is_current', true);
            })->join('taxa', 'taxa.id', '=', 'determinations.taxon_id'),
            'locality' => fn ($builder) => $builder->join('collecting_events', 'collecting_events.id', '=', 'specimens.collecting_event_id')->join('localities', 'localities.id', '=', 'collecting_events.locality_id'),
            'event' => fn ($builder) => $builder->join('collecting_events', 'collecting_events.id', '=', 'specimens.collecting_event_id'),
            'preparation' => fn ($builder) => $builder->join('preparations', 'preparations.specimen_id', '=', 'specimens.id'),
            'determination' => fn ($builder) => $builder->join('determinations', function ($join): void {
                $join->on('determinations.specimen_id', '=', 'specimens.id')->where('determinations.is_current', true);
            }),
            'specimen' => fn ($builder) => $builder,
        ];

        return DB::query()
            ->fromSub($base, 'matching_specimens')
            ->join('specimens', 'specimens.id', '=', 'matching_specimens.id')
            ->tap($joins[$definition['relation']])
            ->selectRaw($definition['column'].' as value, COUNT(DISTINCT specimens.id) as count')
            ->whereNotNull($definition['column'])
            ->where($definition['column'], '!=', '')
            ->groupBy($definition['column']);
    }
}
