@extends('layouts.portal')

@section('title', 'Project Status')
@section('header', 'Project Status')

@section('content')
    <section class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Environment</p>
            <dl class="mt-5 space-y-4 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Laravel</dt>
                    <dd class="font-medium text-slate-950">{{ $report['application']['laravel'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">PHP</dt>
                    <dd class="font-medium text-slate-950">{{ $report['application']['php'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Database driver</dt>
                    <dd class="font-medium text-slate-950">{{ $report['application']['database_driver'] }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Connection</dt>
                    <dd class="font-medium text-slate-950">{{ $report['application']['database_connection'] }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-slate-950 p-6 text-slate-100 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-cyan-300">Fish status</p>
            <h2 class="mt-3 text-2xl font-semibold">Incoming dataset detected but not yet committed.</h2>
            <p class="mt-3 text-slate-300">{{ $report['fish']['incoming_status'] }}</p>
            @if ($report['fish']['incoming_files'] !== [])
                <ul class="mt-5 space-y-3">
                    @foreach ($report['fish']['incoming_files'] as $file)
                        <li class="rounded-2xl bg-white/5 px-4 py-3">
                            <div class="font-medium">{{ $file['name'] }}</div>
                            <div class="mt-1 text-sm text-slate-300">{{ $file['size'] }}</div>
                        </li>
                    @endforeach
                </ul>
            @endif
            <p class="mt-4 text-sm text-slate-300">Imported specimens: {{ $report['fish']['imported_specimens'] }}</p>
        </div>
    </section>

    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Collections</p>
        <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Collection</th>
                        <th class="px-4 py-3 font-semibold">Imported Records</th>
                        <th class="px-4 py-3 font-semibold">Incoming Files</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @foreach ($report['collections'] as $summary)
                        @php($collection = $summary['collection'])
                        <tr>
                            <td class="px-4 py-4 font-medium text-slate-950">{{ $collection->name }}</td>
                            <td class="px-4 py-4">{{ $summary['imported_specimens'] }}</td>
                            <td class="px-4 py-4">{{ $summary['incoming_file_count'] }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $summary['incoming_status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-8 grid gap-6 lg:grid-cols-[1fr_0.9fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Import pipeline</p>
            <ul class="mt-5 space-y-3">
                @foreach ($report['pipeline'] as $capability)
                    <li class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3">
                        <span>{{ $capability['label'] }}</span>
                        <span class="font-semibold {{ $capability['state'] === 'enabled' ? 'text-teal-700' : 'text-slate-500' }}">
                            {{ $capability['state'] === 'enabled' ? '[x]' : '[ ]' }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Next recommended step</p>
            <p class="mt-4 text-lg leading-8 text-slate-800">{{ $report['next_recommended_step'] }}</p>
        </div>
    </section>
@endsection
