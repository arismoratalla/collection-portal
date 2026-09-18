@extends('layouts.portal')

@section('title', $summary['collection']->name)
@section('header', $summary['collection']->name)

@section('content')
    @php($collection = $summary['collection'])
    @php($visual = config("collection-visuals.collections.{$collection->slug}", config('collection-visuals.default')))
    <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Collection overview</p>
                    <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $collection->name }}</h2>
                    <p class="mt-2 text-sm font-medium text-teal-700">{{ $visual['discipline'] ?? 'Public collection' }}</p>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                        {{ $collection->description ?: ($visual['description'] ?? 'Public specimen records and catalogued holdings.') }}
                    </p>
                </div>

                <a href="{{ route('collections.search', $collection) }}" class="inline-flex items-center justify-center rounded-full bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-teal-800">
                    Search this collection
                </a>
            </div>

            @if ($summary['imported_specimens'] === 0)
                <div class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                    Public specimen records are being prepared for this collection.
                </div>
            @else
                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Public specimen records</dt>
                        <dd class="mt-1 text-2xl font-semibold text-slate-950">{{ $summary['imported_specimens'] }}</dd>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <dt class="text-sm text-slate-500">Collection focus</dt>
                        <dd class="mt-1 text-base font-semibold text-slate-950">{{ $visual['discipline'] ?? $collection->name }}</dd>
                    </div>
                </dl>
            @endif
        </div>

        <aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="aspect-[4/3] overflow-hidden rounded-3xl border border-slate-200"
                style="background: linear-gradient(135deg, {{ $visual['soft'] ?? '#ccfbf1' }} 0%, #ffffff 46%, {{ $visual['accent'] ?? '#0f766e' }} 100%);">
                <div
                    class="h-full w-full"
                    style="background:
                        radial-gradient(circle at 18% 22%, rgba(255,255,255,.9) 0 10%, transparent 11%),
                        radial-gradient(circle at 76% 22%, rgba(255,255,255,.6) 0 8%, transparent 9%),
                        radial-gradient(circle at 52% 74%, rgba(255,255,255,.45) 0 15%, transparent 16%);
                    "
                ></div>
            </div>

            <div class="mt-6 rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Public status</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    {{ $summary['imported_specimens'] === 0 ? 'Public specimen records are being prepared for this collection.' : 'Public specimen records are available for browsing and search.' }}
                </p>
            </div>
        </aside>
    </section>
@endsection
