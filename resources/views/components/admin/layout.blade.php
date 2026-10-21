@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? "$title · " : '' }}{{ config('app.name') }} CMS</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="h-full text-slate-800 antialiased" x-data="{ sidebarOpen: false }">
<div class="min-h-full">
    {{-- Mobile backdrop --}}
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" @click="sidebarOpen = false"></div>

    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full bg-slate-900 transition-transform lg:translate-x-0"
           :class="sidebarOpen && 'translate-x-0'">
        <div class="flex h-16 items-center gap-2 px-6 text-lg font-bold uppercase tracking-wide text-white">
            <span class="inline-block size-3 rounded-full bg-emerald-400"></span>
            {{ config('app.name') }}
        </div>
        <x-admin.sidebar />
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-4 sm:px-6">
            <button type="button" class="lg:hidden" @click="sidebarOpen = true" aria-label="Open menu">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>
            <h1 class="text-lg font-semibold">{{ $title }}</h1>
            <div class="ml-auto flex items-center gap-4 text-sm">
                <span class="hidden sm:inline text-slate-500">{{ auth()->user()->name }} · {{ auth()->user()->primaryRole()?->label() }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="rounded-md border border-slate-300 px-3 py-1.5 hover:bg-slate-50">Log out</button>
                </form>
            </div>
        </header>

        <main class="p-4 sm:p-6">
            <x-admin.flash />
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
