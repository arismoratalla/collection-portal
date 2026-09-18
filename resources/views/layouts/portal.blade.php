<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light">
        <meta name="portal-map-tile-url" content="{{ config('portal-map.tile_url') }}">
        <meta name="portal-map-attribution" content="{{ config('portal-map.attribution') }}">

        <title>@yield('title', config('app.name', 'Research Collections Portal'))</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    @php($isSearchPage = request()->routeIs('collections.search'))
    <body class="{{ $isSearchPage ? 'bg-slate-50 text-slate-900 antialiased lg:h-screen lg:overflow-hidden' : 'min-h-screen bg-slate-50 text-slate-900 antialiased' }}">
        <div class="relative isolate {{ $isSearchPage ? 'min-h-screen lg:flex lg:h-screen lg:flex-col lg:overflow-hidden' : 'overflow-hidden' }}">
            <div class="absolute inset-x-0 top-0 -z-10 h-64 bg-gradient-to-b from-teal-100 via-cyan-50 to-transparent"></div>

            <header class="border-b border-slate-200/80 bg-white/85 backdrop-blur">
                <div class="{{ $isSearchPage ? 'flex flex-col gap-3 px-4 py-3 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-7' : 'mx-auto flex max-w-7xl flex-col gap-4 px-6 py-5 lg:flex-row lg:items-center lg:justify-between lg:px-8' }}">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-slate-500">Research Collections Portal</p>
                        <div class="mt-1 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <h1 class="text-xl font-semibold tracking-tight text-slate-950 {{ $isSearchPage ? 'lg:text-2xl' : 'text-2xl' }}">@yield('header', 'Research Collections Portal')</h1>
                            @hasSection('header-subtitle')
                                <p class="text-sm text-slate-500">@yield('header-subtitle')</p>
                            @endif
                        </div>
                    </div>

                    <nav aria-label="Primary" class="flex flex-wrap gap-3 text-sm font-medium">
                        <div class="flex flex-col gap-1.5">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.35em] text-slate-400">Public</p>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('home') }}" class="rounded-full px-4 py-2 {{ request()->routeIs('home') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">Home</a>
                                <a href="{{ route('collections.index') }}" class="rounded-full px-4 py-2 {{ request()->routeIs('collections.*') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">Collections</a>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5 border-l border-slate-200 pl-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.35em] text-slate-400">Operations</p>
                            <a href="{{ route('workflow') }}" class="rounded-full px-4 py-2 {{ request()->routeIs('workflow') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">Workflow</a>
                            <a href="{{ route('status') }}" class="rounded-full px-4 py-2 {{ request()->routeIs('status') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">Project Status</a>
                        </div>
                    </nav>
                </div>
            </header>

            <main class="{{ $isSearchPage ? 'min-h-0 px-3 py-3 sm:px-4 lg:flex-1 lg:overflow-hidden lg:px-4 lg:py-3' : 'mx-auto max-w-7xl px-6 py-10 lg:px-8' }}">
                @yield('content')
            </main>
        </div>
    </body>
</html>
