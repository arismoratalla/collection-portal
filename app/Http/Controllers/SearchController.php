<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchSpecimenRequest;
use App\Models\Collection;
use App\Models\Specimen;
use App\Services\ProjectStatusService;
use App\Services\Search\SpecimenFacetService;
use App\Services\Search\SpecimenMapService;
use App\Services\Search\SpecimenSearchQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(
        SearchSpecimenRequest $request,
        Collection $collection,
        SpecimenSearchQuery $query,
        SpecimenFacetService $facets,
        ProjectStatusService $status,
    ): View {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? config('collection-search.default_page_size', 50));
        $summary = $status->collectionDetail($collection);
        $results = $query->search($collection, $filters, $perPage);

        return view('portal.collections.search', [
            'collection' => $collection,
            'summary' => $summary,
            'filters' => $filters,
            'results' => $results,
            'facetResults' => $facets->initial($collection, $filters),
            'facetKeys' => $facets->facetsFor($collection),
            'facetDefinitions' => config('collection-search.facet_definitions', []),
            'columns' => config("collection-search.collections.{$collection->slug}.columns", config('collection-search.default.columns', [])),
            'emptyCollection' => $summary['imported_specimens'] === 0,
        ]);
    }

    public function facet(SearchSpecimenRequest $request, Collection $collection, string $facet, SpecimenFacetService $facets): JsonResponse
    {
        try {
            return response()->json($facets->facet(
                $collection,
                $facet,
                $request->validated(),
                $request->string('facet_search')->trim()->toString() ?: null,
                max(1, $request->integer('facet_page', 1)),
            ));
        } catch (\InvalidArgumentException) {
            abort(404);
        }
    }

    public function mapData(SearchSpecimenRequest $request, Collection $collection, SpecimenMapService $map): JsonResponse
    {
        $bounds = $this->bounds($request);

        return response()->json($map->markers($collection, $request->validated(), $bounds));
    }

    /** @return array{north: float, south: float, east: float, west: float}|null */
    protected function bounds(Request $request): ?array
    {
        $keys = ['north', 'south', 'east', 'west'];

        if (collect($keys)->contains(fn (string $key): bool => ! $request->filled($key))) {
            return null;
        }

        $bounds = collect($keys)->mapWithKeys(fn (string $key): array => [$key => (float) $request->input($key)])->all();

        if ($bounds['north'] < $bounds['south'] || $bounds['east'] < $bounds['west'] || $bounds['north'] > 90 || $bounds['south'] < -90 || $bounds['east'] > 180 || $bounds['west'] < -180) {
            abort(422);
        }

        return $bounds;
    }

    public function show(Specimen $specimen): View
    {
        $specimen->load([
            'collection',
            'collectingEvent.locality.geography.parent.parent.parent',
            'currentDetermination.taxon',
            'determinations.taxon',
            'preparations',
        ]);

        return view('portal.specimens.show', [
            'specimen' => $specimen,
        ]);
    }
}
