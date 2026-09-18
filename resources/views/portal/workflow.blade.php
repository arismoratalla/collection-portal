@extends('layouts.portal')

@section('title', 'Workflow')
@section('header', 'Workflow')

@section('content')
    <section class="grid gap-8 lg:grid-cols-[1fr_0.7fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Source data workflow</p>
            <pre class="mt-4 overflow-x-auto rounded-2xl bg-slate-950 p-5 text-sm leading-7 text-slate-100"><code>Collection Manager
    ↓
MS Access
    ↓
IPT Exporter
    ↓
Darwin Core CSV
    ├── External Aggregators
    └── Collection Portal
            ↓
          Laravel
            ↓
           MySQL
            ↓
      Public Discovery</code></pre>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-teal-50 p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-teal-700">Resume guide</p>
            <h2 class="mt-3 text-2xl font-semibold tracking-tight text-slate-950">A safe, repeatable path back into the project</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">Every step below is read-only until a committed import is intentionally implemented later.</p>
        </div>
    </section>

    <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <ol class="space-y-5">
            @foreach ($workflowSteps as $step)
                <li class="rounded-2xl bg-slate-50 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Step {{ $step['step'] }}</p>
                            <h3 class="mt-1 text-lg font-semibold text-slate-950">{{ $step['title'] }}</h3>
                        </div>
                        @if (($step['command'] ?? null) !== null)
                            <code class="rounded-full bg-slate-950 px-3 py-1 text-xs text-slate-100">{{ $step['command'] }}</code>
                        @endif
                    </div>

                    @if (($step['alt_command'] ?? null) !== null)
                        <p class="mt-3 text-sm text-slate-600">or <code>{{ $step['alt_command'] }}</code></p>
                    @endif

                    @if (($step['note'] ?? null) !== null)
                        <p class="mt-3 text-sm font-medium text-teal-700">{{ $step['note'] }}</p>
                    @endif
                </li>
            @endforeach
        </ol>
    </section>

    <section class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white p-6 text-slate-700 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-slate-500">Next recommended step</p>
        <p class="mt-3 text-lg">{{ $nextRecommendedStep }}</p>
    </section>
@endsection
