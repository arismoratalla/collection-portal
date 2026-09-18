@extends('layouts.portal')

@section('title', 'Collections')
@section('header', 'Collections')

@section('content')
    @php
        $collectionOrder = array_keys(config('collection-visuals.collections', []));
        $orderedCollections = collect($collections)
            ->sortBy(fn (array $summary): int => array_search($summary['collection']->slug, $collectionOrder, true));
    @endphp

    <section class="max-w-3xl">
        <h2 class="text-4xl font-semibold tracking-tight text-slate-950">Collections</h2>
    </section>

    <section class="mt-10">
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($orderedCollections as $summary)
                <x-collection-card :collection="$summary['collection']" />
            @endforeach
        </div>
    </section>
@endsection
