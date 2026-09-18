@extends('layouts.portal')

@section('title', $collection->name.' Search')
@section('header', $collection->name.' Search')
@section('header-subtitle', config("collection-visuals.collections.{$collection->slug}.discipline", config('collection-visuals.default.discipline')))

@section('content')
    @php
        $facetGroups = collect($facetKeys)->groupBy(fn (string $key) => $facetDefinitions[$key]['group'] ?? 'Other');
        $activeFilters = collect($filters)->filter(fn (mixed $value, string $key): bool => ! in_array($key, ['sort', 'direction', 'per_page'], true) && filled($value));
        $queryWithout = function (string $key, ?string $value = null) use ($filters): string {
            $next = $filters;
            if ($value !== null && isset($next[$key]) && is_array($next[$key])) {
                $next[$key] = array_values(array_filter($next[$key], fn (string $selected): bool => $selected !== $value));
                if ($next[$key] === []) unset($next[$key]);
            } else {
                unset($next[$key]);
            }
            unset($next['page']);
            return route('collections.search', request()->route('collection')).(count($next) ? '?'.http_build_query($next) : '');
        };
    @endphp

    <form id="collection-search-form" method="get" action="{{ route('collections.search', $collection) }}" class="grid gap-3 lg:h-full lg:grid-cols-[21rem_minmax(0,1fr)] lg:gap-0" data-search-workspace>
        <aside class="border-b border-slate-200 bg-white lg:min-h-0 lg:overflow-y-auto lg:border-r lg:border-b-0">
            <div class="p-4 sm:p-5 lg:p-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-950">Filter records</h3>
                    <a href="{{ route('collections.search', $collection) }}" class="text-sm font-semibold text-teal-700 hover:text-teal-800">Clear all</a>
                </div>

                <details open class="mt-4 border-t border-slate-100 pt-3">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-900">Catalog / Identifiers</summary>
                    <div class="mt-2 space-y-2">
                        <label class="block text-sm text-slate-600">Catalog Number<input name="catalog_number" value="{{ $filters['catalog_number'] ?? '' }}" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:ring-teal-600"></label>
                        <label class="block text-sm text-slate-600">Field Number<input name="field_number" value="{{ $filters['field_number'] ?? '' }}" class="mt-1 w-full rounded-xl border-slate-300 px-3 py-2 text-sm focus:border-teal-600 focus:ring-teal-600"></label>
                    </div>
                </details>

                @foreach ($facetGroups as $group => $groupFacets)
                    <p class="mt-4 text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">{{ $group }}</p>
                    @foreach ($groupFacets as $facetKey)
                        @php($facet = $facetResults[$facetKey] ?? ['key' => $facetKey, 'label' => $facetDefinitions[$facetKey]['label'], 'values' => [], 'has_more' => false])
                        @php($selected = collect($filters[$facetKey] ?? [])->filter()->all())
                        <details class="group border-b border-slate-100 py-3" @if(in_array($facetKey, ['family', 'genus', 'specific_epithet', 'country', 'state', 'county', 'locality'], true)) open @endif>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-slate-900">
                                <span>{{ $facet['label'] }}</span><span aria-hidden="true" class="text-slate-400 group-open:rotate-45">+</span>
                            </summary>
                            <div class="mt-2" data-facet="{{ $facetKey }}" data-loaded="{{ $facet['values'] !== [] ? 'true' : 'false' }}" data-url="{{ route('collections.facets.show', [$collection, $facetKey]) }}">
                                <label class="sr-only" for="facet-search-{{ $facetKey }}">Search {{ $facet['label'] }} values</label>
                                <input id="facet-search-{{ $facetKey }}" data-facet-search placeholder="Search to narrow options..." class="w-full rounded-lg border-slate-300 px-2.5 py-2 text-xs focus:border-teal-600 focus:ring-teal-600">
                                <div class="mt-2 space-y-1.5" data-facet-values>
                                    @foreach ($facet['values'] as $option)
                                        <label class="flex cursor-pointer items-start gap-2 text-sm text-slate-700">
                                            <input type="checkbox" name="{{ $facetKey }}[]" value="{{ $option['value'] }}" @checked(in_array($option['value'], $selected, true)) class="mt-0.5 rounded border-slate-300 text-teal-700 focus:ring-teal-600">
                                            <span class="min-w-0 flex-1 break-words">{{ $option['value'] }}</span><span class="text-xs text-slate-400">{{ number_format($option['count']) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @if ($facet['has_more'])
                                    <button type="button" data-load-more class="mt-2 text-xs font-semibold text-teal-700 hover:text-teal-800">Load more</button>
                                @endif
                            </div>
                        </details>
                    @endforeach
                @endforeach

                <details class="border-b border-slate-100 py-3">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-900">Date and public coordinates</summary>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <label class="text-xs text-slate-600">Year from<input name="year_from" value="{{ $filters['year_from'] ?? '' }}" inputmode="numeric" class="mt-1 w-full rounded-lg border-slate-300 px-2 py-2 text-sm"></label>
                        <label class="text-xs text-slate-600">Year to<input name="year_to" value="{{ $filters['year_to'] ?? '' }}" inputmode="numeric" class="mt-1 w-full rounded-lg border-slate-300 px-2 py-2 text-sm"></label>
                        <label class="text-xs text-slate-600">Latitude min<input name="latitude_min" value="{{ $filters['latitude_min'] ?? '' }}" inputmode="decimal" class="mt-1 w-full rounded-lg border-slate-300 px-2 py-2 text-sm"></label>
                        <label class="text-xs text-slate-600">Latitude max<input name="latitude_max" value="{{ $filters['latitude_max'] ?? '' }}" inputmode="decimal" class="mt-1 w-full rounded-lg border-slate-300 px-2 py-2 text-sm"></label>
                        <label class="text-xs text-slate-600">Longitude min<input name="longitude_min" value="{{ $filters['longitude_min'] ?? '' }}" inputmode="decimal" class="mt-1 w-full rounded-lg border-slate-300 px-2 py-2 text-sm"></label>
                        <label class="text-xs text-slate-600">Longitude max<input name="longitude_max" value="{{ $filters['longitude_max'] ?? '' }}" inputmode="decimal" class="mt-1 w-full rounded-lg border-slate-300 px-2 py-2 text-sm"></label>
                    </div>
                </details>

                <button type="submit" class="mt-4 w-full rounded-full bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-800">Search</button>
            </div>
        </aside>

        <section class="flex min-w-0 flex-col gap-3 lg:min-h-0 lg:overflow-hidden lg:pl-3">
            @if ($activeFilters->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-white px-3 py-2">
                    <span class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Active filters</span>
                    @foreach ($activeFilters as $key => $values)
                        @foreach (is_array($values) ? $values : [$values] as $value)
                            <a href="{{ $queryWithout($key, is_string($value) ? $value : null) }}" class="rounded-full bg-teal-50 px-3 py-1 text-xs font-medium text-teal-800 hover:bg-teal-100">{{ $facetDefinitions[$key]['label'] ?? Str::headline($key) }}: {{ $value }} <span aria-hidden="true">×</span></a>
                        @endforeach
                    @endforeach
                </div>
            @endif

            <section data-collection-map data-map-url="{{ route('collections.map-data', $collection) }}" data-specimen-url="{{ url('/specimens') }}" class="shrink-0 border border-slate-200 bg-white p-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h3 class="font-semibold text-slate-950">Map</h3><p class="text-sm text-slate-500" data-map-status>Loading public mapped records...</p></div>
                    <div class="flex gap-2"><button type="button" data-map-toggle class="rounded-full border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700">Hide map</button><button type="button" data-map-fit class="rounded-full border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700">Fit results</button></div>
                </div>
                <div id="collection-map" class="mt-3 h-56 overflow-hidden bg-slate-100 lg:h-[21rem]" role="region" aria-label="Public specimen map"></div>
                <p class="hidden py-8 text-center text-sm text-slate-500" data-map-empty>No mapped records match the current search.</p>
            </section>

            <section class="flex min-h-0 flex-col border border-slate-200 bg-white lg:flex-1 lg:overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3"><p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Results</p><h3 class="text-base font-semibold text-slate-950">{{ number_format($results->total()) }} records</h3></div>
                    <div class="flex flex-wrap gap-2">
                        <label class="sr-only" for="q">Search all fields</label><input id="q" name="q" value="{{ $filters['q'] ?? $filters['quick_search'] ?? '' }}" placeholder="Search all fields..." class="min-w-48 rounded-lg border-slate-300 px-2.5 py-1.5 text-sm focus:border-teal-600 focus:ring-teal-600">
                        <label class="sr-only" for="sort">Sort results</label><select id="sort" name="sort" class="rounded-lg border-slate-300 text-sm"><option value="catalog_number" @selected(($filters['sort'] ?? '') === 'catalog_number')>Catalog number</option><option value="family" @selected(($filters['sort'] ?? '') === 'family')>Family</option><option value="genus" @selected(($filters['sort'] ?? '') === 'genus')>Genus</option><option value="specific_epithet" @selected(($filters['sort'] ?? '') === 'specific_epithet')>Species</option><option value="event_year" @selected(($filters['sort'] ?? '') === 'event_year')>Event year</option></select>
                        <select name="direction" aria-label="Sort direction" class="rounded-lg border-slate-300 text-sm"><option value="asc" @selected(($filters['direction'] ?? '') !== 'desc')>Ascending</option><option value="desc" @selected(($filters['direction'] ?? '') === 'desc')>Descending</option></select>
                        <select name="per_page" aria-label="Results per page" class="rounded-lg border-slate-300 text-sm"><option value="25" @selected(($filters['per_page'] ?? 50) == 25)>25</option><option value="50" @selected(($filters['per_page'] ?? 50) == 50)>50</option><option value="100" @selected(($filters['per_page'] ?? 50) == 100)>100</option></select>
                    </div>
                </div>

                <div class="min-h-0 overflow-auto">
                    <table class="min-w-[75rem] w-full table-fixed divide-y divide-slate-200 text-left text-[13px] leading-5">
                        <thead class="sticky top-0 z-10 bg-slate-50 text-slate-600"><tr>
                            @foreach ($columns as $column)<th class="px-3 py-2 font-semibold">{{ Str::headline(str_replace('_', ' ', $column)) }}</th>@endforeach
                            <th class="w-20 px-3 py-2 font-semibold">View</th>
                        </tr></thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($results as $specimen)
                                @php($taxon = $specimen->currentDetermination?->taxon)
                                @php($locality = $specimen->collectingEvent?->locality)
                                @php($geography = $locality?->geography)
                                <tr class="align-top text-slate-700">
                                    @foreach ($columns as $column)
                                        @php($value = match ($column) {
                                            'field_number' => $specimen->collectingEvent?->field_number,
                                            'family', 'genus', 'specific_epithet', 'infraspecific_epithet', 'vernacular_name' => $taxon?->{$column},
                                            'preparation' => $specimen->preparations->pluck('source_value')->filter()->join('; '),
                                            'country', 'state', 'county' => $geography?->ancestorByType($column)?->name,
                                            'event_year' => $specimen->collectingEvent?->event_year,
                                            default => $specimen->{$column},
                                        })
                                        <td class="px-3 py-2 break-words">{{ $value ?: '—' }}</td>
                                    @endforeach
                                    <td class="px-3 py-2"><a href="{{ route('specimens.show', $specimen) }}" class="font-semibold text-teal-700 hover:text-teal-800">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ count($columns) + 1 }}" class="px-6 py-10 text-center text-slate-500">{{ $emptyCollection ? 'No public specimen records are currently available for this collection.' : 'No specimen records matched your search.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($results->total() > 0)<div class="flex shrink-0 items-center justify-between gap-4 border-t border-slate-200 px-3 py-2.5 text-sm text-slate-600"><span>Page {{ $results->currentPage() }} of {{ $results->lastPage() }}</span>{{ $results->links() }}</div>@endif
            </section>
        </section>
    </form>
@endsection
