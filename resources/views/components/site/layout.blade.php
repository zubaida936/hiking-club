{{--
    SEO: $description, $image (share image: absolute URL or path under public/), $type (og:type) and
    $noindex (placeholder/utility pages). $whatsapp: pre-filled WhatsApp message for this page.
    Extra <head> tags (e.g. JSON-LD) can be added with @push('head').
--}}
@props(['title' => null, 'description' => null, 'image' => null, 'type' => 'website', 'noindex' => false, 'whatsapp' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-site.seo :title="$title" :description="$description" :image="$image" :type="$type" :noindex="$noindex" />
    @fonts(['jost', 'roboto'])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    <x-site.analytics />
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-slate-800 antialiased">
    <x-site.announcement-bar />
    <x-site.header />

    <main class="flex-1">
        {{ $slot }}
    </main>

    <x-site.footer />

    <x-site.whatsapp-button :message="$whatsapp" />
</body>
</html>
