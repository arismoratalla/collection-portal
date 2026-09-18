@extends('layouts.portal')

@section('title', 'Research Collections Portal')
@section('header', 'Research Collections Portal')

@section('content')
    @php
        $collectionOrder = array_keys(config('collection-visuals.collections', []));
        $collections = collect($report['collections'])
            ->sortBy(fn (array $summary): int => array_search($summary['collection']->slug, $collectionOrder, true));
    @endphp

    <section class="max-w-3xl">
        <h2 class="text-4xl font-semibold tracking-tight text-slate-950">Explore the Museum's research collections.</h2>
    </section>

    <section class="mt-10">
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($collections as $summary)
                <x-collection-card :collection="$summary['collection']" />
            @endforeach
        </div>
    </section>
@endsection
