@props(['collection'])

@php
    $visual = config("collection-visuals.collections.{$collection->slug}", config('collection-visuals.default'));
    $imagePath = $visual['image'] ?? null;
    $imageExists = is_string($imagePath) && file_exists(public_path($imagePath));
    $imageAlt = $collection->name.' collection artwork';
    $accent = $visual['accent'] ?? '#0f766e';
    $soft = $visual['soft'] ?? '#ccfbf1';
@endphp

<a href="{{ route('collections.search', $collection) }}" class="group block h-full focus:outline-none" aria-label="Open {{ $collection->name }} search">
    <article class="flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2">
        <div
            class="relative aspect-[4/3] overflow-hidden border-b border-slate-200"
            style="background: linear-gradient(135deg, {{ $soft }} 0%, #ffffff 48%, {{ $accent }} 100%);"
        >
            @if ($imageExists)
                <img
                    src="{{ asset($imagePath) }}"
                    alt="{{ $imageAlt }}"
                    class="h-full w-full object-contain p-6 transition duration-200 group-hover:scale-[1.02]"
                    loading="lazy"
                >
            @else
                <div
                    class="absolute inset-0"
                    aria-hidden="true"
                    style="background:
                        radial-gradient(circle at 25% 28%, rgba(255,255,255,.9) 0 13%, transparent 14%),
                        radial-gradient(circle at 72% 24%, rgba(255,255,255,.55) 0 10%, transparent 11%),
                        radial-gradient(circle at 53% 72%, rgba(255,255,255,.38) 0 18%, transparent 19%);
                    "
                ></div>
            @endif
        </div>

        <div class="flex flex-1 items-center px-6 py-5">
            <h3 class="text-lg font-semibold tracking-tight text-slate-950 sm:text-xl">
                {{ $collection->name }}
            </h3>
        </div>
    </article>
</a>
