@extends('layouts.portal')

@section('title', $specimen->occurrence_id)
@section('header', $specimen->catalog_number)

@section('content')
    @php
        $locality = $specimen->collectingEvent?->locality;
        $geography = $locality?->geography;
        $country = $geography?->ancestorByType('country');
        $state = $geography?->ancestorByType('state');
        $county = $geography?->ancestorByType('county');
        $currentTaxon = $specimen->currentDetermination?->taxon;
    @endphp

    <section class="grid gap-6 lg:grid-cols-[1fr_0.8fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Specimen</p>
            <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $specimen->scientific_name ?? $currentTaxon?->scientific_name ?? 'Specimen record' }}</h2>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Collection</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-950">{{ $specimen->collection?->name }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Catalog Number</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-950">{{ $specimen->catalog_number }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Occurrence ID</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-950">{{ $specimen->occurrence_id }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Type Status</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-950">{{ $specimen->type_status ?? '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Individual Count</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-950">{{ $specimen->individual_count ?? '—' }}</dd>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4">
                    <dt class="text-sm text-slate-500">Current Determination</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-950">{{ $currentTaxon?->scientific_name ?? '—' }}</dd>
                </div>
            </dl>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Taxonomy</p>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-slate-500">Kingdom</dt>
                        <dd class="font-medium text-slate-950">{{ $currentTaxon?->kingdom ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Phylum</dt>
                        <dd class="font-medium text-slate-950">{{ $currentTaxon?->phylum ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Class</dt>
                        <dd class="font-medium text-slate-950">{{ $currentTaxon?->class_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Order</dt>
                        <dd class="font-medium text-slate-950">{{ $currentTaxon?->order_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Family</dt>
                        <dd class="font-medium text-slate-950">{{ $currentTaxon?->family ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-slate-500">Genus</dt>
                        <dd class="font-medium text-slate-950">{{ $currentTaxon?->genus ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm text-slate-500">Scientific Name</dt>
                        <dd class="font-medium text-slate-950">{{ $currentTaxon?->scientific_name ?? $specimen->scientific_name ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <aside class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Collecting Event</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Field Number</dt>
                        <dd class="font-medium text-slate-950">{{ $specimen->collectingEvent?->field_number ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Year</dt>
                        <dd class="font-medium text-slate-950">{{ $specimen->collectingEvent?->event_year ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Month</dt>
                        <dd class="font-medium text-slate-950">{{ $specimen->collectingEvent?->event_month ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Day</dt>
                        <dd class="font-medium text-slate-950">{{ $specimen->collectingEvent?->event_day ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Verbatim Date</dt>
                        <dd class="font-medium text-slate-950">{{ $specimen->collectingEvent?->verbatim_event_date ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Locality</p>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Locality</dt>
                        <dd class="font-medium text-slate-950">{{ $locality?->locality ?? $locality?->verbatim_locality ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Country</dt>
                        <dd class="font-medium text-slate-950">{{ $country?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">State / Province</dt>
                        <dd class="font-medium text-slate-950">{{ $state?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">County</dt>
                        <dd class="font-medium text-slate-950">{{ $county?->name ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($locality && $locality->coordinates_public && ! $locality->sensitive && $locality->decimal_latitude !== null && $locality->decimal_longitude !== null)
                    <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                        <p class="font-semibold text-slate-900">Public Coordinates</p>
                        <p class="mt-2">Latitude: {{ $locality->decimal_latitude }}</p>
                        <p>Longitude: {{ $locality->decimal_longitude }}</p>
                        @if ($locality->coordinate_uncertainty_meters !== null)
                            <p>Uncertainty: {{ $locality->coordinate_uncertainty_meters }} m</p>
                        @endif
                    </div>
                @else
                    <div class="mt-5 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                        Coordinates withheld.
                    </div>
                @endif
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Preparations</p>
                @if ($specimen->preparations->isEmpty())
                    <p class="mt-4 text-sm text-slate-600">No preparations recorded.</p>
                @else
                    <ul class="mt-4 space-y-3">
                        @foreach ($specimen->preparations as $preparation)
                            <li class="rounded-2xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                {{ $preparation->source_value ?? $preparation->preparation_type ?? '—' }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </aside>
    </section>
@endsection
