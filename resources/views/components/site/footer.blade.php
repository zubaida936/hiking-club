@php
    $contact = config('site.contact');
    $social = config('site.social');

    // network => [label, profile URL]; accounts left empty in config are not shown.
    $socialLinks = array_filter([
        'facebook' => $social['facebook'] ? ['Facebook', "https://www.facebook.com/{$social['facebook']}"] : null,
        'instagram' => $social['instagram'] ? ['Instagram', "https://www.instagram.com/{$social['instagram']}"] : null,
        'tiktok' => $social['tiktok'] ? ['TikTok', "https://www.tiktok.com/@{$social['tiktok']}"] : null,
        'youtube' => $social['youtube'] ? ['YouTube', "https://www.youtube.com/@{$social['youtube']}"] : null,
        'whatsapp' => $contact['whatsapp'] ? ['WhatsApp', "https://wa.me/{$contact['whatsapp']}"] : null,
    ]);

    // Site map from the shared menu: plain items under "Explore" (About and Contact
    // move to "Support"), and each dropdown group as its own column.
    $support = ['about', 'contact'];
    $columns = ['Explore' => []];
    foreach (config('site.menu') as $item) {
        if (isset($item['children'])) {
            $columns[$item['label']] = $item['children'];
        } elseif (! in_array($item['route'], $support, true)) {
            $columns['Explore'][] = $item;
        }
    }
    $columns['Support'] = [
        ['label' => 'About', 'route' => 'about'],
        ['label' => 'Contact', 'route' => 'contact'],
        ['label' => 'Privacy Policy', 'route' => 'legal.privacy'],
    ];
@endphp

<footer class="bg-brand-900 text-white/80">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <x-site.logo class="text-white" />
            <p class="mt-4 max-w-sm">{{ config('site.tagline') }}</p>

            <address class="mt-6 space-y-2 not-italic">
                <p>{{ $contact['address'] }}</p>
                <p><a href="tel:{{ preg_replace('/[^\d+]/', '', $contact['phone']) }}" class="hover:text-accent-400">{{ $contact['phone'] }}</a></p>
                <p><a href="mailto:{{ $contact['email'] }}" class="hover:text-accent-400">{{ $contact['email'] }}</a></p>
            </address>

            @if ($socialLinks)
                <ul class="mt-6 flex gap-3" aria-label="Social media">
                    @foreach ($socialLinks as $network => [$label, $url])
                        <li>
                            <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}"
                               class="flex size-10 items-center justify-center rounded-full bg-white/10 hover:bg-accent-500 hover:text-white">
                                <x-site.social-icon :name="$network" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        @foreach ($columns as $heading => $links)
            <nav aria-label="{{ $heading }}">
                <h2 class="font-display text-sm font-semibold uppercase tracking-widest text-white">{{ $heading }}</h2>
                <ul class="mt-4 space-y-2">
                    @foreach ($links as $link)
                        <li><a href="{{ route($link['route']) }}" class="hover:text-accent-400">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endforeach
    </div>

    <div class="border-t border-white/10">
        <p class="mx-auto max-w-7xl px-4 py-6 text-sm text-white/60 sm:px-6">
            &copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.
        </p>
    </div>
</footer>
