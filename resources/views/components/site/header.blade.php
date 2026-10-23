@php($items = config('site.menu'))

<header class="sticky top-0 z-40 bg-brand-900 text-white shadow" x-data="{ mobileOpen: false }" @keydown.escape.window="mobileOpen = false">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:h-20">
        <x-site.logo />

        {{-- Desktop menu --}}
        <nav class="hidden flex-1 items-center justify-center gap-1 lg:flex" aria-label="Main">
            @foreach ($items as $item)
                @php($active = request()->routeIs($item['active']))
                @isset($item['children'])
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                        <button type="button" @click="open = ! open" :aria-expanded="open" aria-haspopup="true"
                                @class([
                                    'flex items-center gap-1 rounded px-3 py-2 font-display text-sm font-semibold uppercase tracking-wide hover:text-accent-400',
                                    'text-accent-400' => $active,
                                ])>
                            {{ $item['label'] }}
                            <svg class="size-4 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak x-transition.origin.top
                             class="absolute left-1/2 mt-2 w-60 -translate-x-1/2 rounded-md bg-white py-2 text-slate-700 shadow-lg ring-1 ring-black/5">
                            @foreach ($item['children'] as $child)
                                <a href="{{ route($child['route']) }}"
                                   @class([
                                       'block px-4 py-2 text-sm hover:bg-brand-50 hover:text-brand-700',
                                       'font-semibold text-brand-700' => request()->routeIs($child['route']),
                                   ])>
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                       @class([
                           'rounded px-3 py-2 font-display text-sm font-semibold uppercase tracking-wide hover:text-accent-400',
                           'text-accent-400' => $active,
                       ])>
                        {{ $item['label'] }}
                    </a>
                @endisset
            @endforeach
        </nav>

        <div class="ml-auto flex items-center gap-1 lg:ml-0">
            <a href="{{ route('account') }}" class="rounded-full p-2 hover:bg-white/10" aria-label="My account">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.12a7.5 7.5 0 0 1 15 0A17.93 17.93 0 0 1 12 21.75c-2.68 0-5.22-.59-7.5-1.63Z"/>
                </svg>
            </a>
            @php($cartCount = app(\App\Domain\Shop\Cart::class)->count())
            <a href="{{ route('cart') }}" class="relative rounded-full p-2 hover:bg-white/10" aria-label="Cart{{ $cartCount ? ' ('.$cartCount.' '.Str::plural('item', $cartCount).')' : '' }}">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.39c.51 0 .95.34 1.09.84l.38 1.44m0 0L6.75 12h10.5l2.25-6.72H5.11ZM7.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm9 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM6.75 12 6 15h12"/>
                </svg>
                @if ($cartCount)
                    <span class="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-accent-500 px-1 text-xs font-bold text-white" aria-hidden="true">{{ $cartCount }}</span>
                @endif
            </a>
            <a href="{{ route('events.index') }}"
               class="ml-2 hidden rounded-full bg-accent-500 px-5 py-2.5 font-display text-sm font-bold uppercase tracking-wide text-white hover:bg-accent-600 sm:inline-block">
                Reserve Your Spot
            </a>
            <button type="button" class="ml-1 rounded p-2 hover:bg-white/10 lg:hidden" @click="mobileOpen = ! mobileOpen"
                    :aria-expanded="mobileOpen" aria-controls="mobile-menu" aria-label="Menu">
                <svg x-show="! mobileOpen" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                <svg x-show="mobileOpen" x-cloak class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    <nav id="mobile-menu" x-show="mobileOpen" x-cloak x-transition class="border-t border-white/10 lg:hidden" aria-label="Mobile">
        <div class="space-y-1 px-4 py-4">
            @foreach ($items as $item)
                @isset($item['children'])
                    <p class="px-3 pt-3 font-display text-xs font-semibold uppercase tracking-widest text-white/50">{{ $item['label'] }}</p>
                    @foreach ($item['children'] as $child)
                        <a href="{{ route($child['route']) }}"
                           @class([
                               'block rounded px-6 py-2 font-display font-semibold uppercase tracking-wide hover:bg-white/10',
                               'text-accent-400' => request()->routeIs($child['route']),
                           ])>{{ $child['label'] }}</a>
                    @endforeach
                @else
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'block rounded px-3 py-2 font-display font-semibold uppercase tracking-wide hover:bg-white/10',
                           'text-accent-400' => request()->routeIs($item['active']),
                       ])>{{ $item['label'] }}</a>
                @endisset
            @endforeach
            <a href="{{ route('events.index') }}"
               class="mt-4 block rounded-full bg-accent-500 px-5 py-3 text-center font-display font-bold uppercase tracking-wide hover:bg-accent-600">
                Reserve Your Spot
            </a>
        </div>
    </nav>
</header>
