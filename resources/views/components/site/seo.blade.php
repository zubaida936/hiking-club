@props([
    'title' => null,
    'description' => null,
    'image' => null,     // absolute URL or path under public/; defaults to the site share image
    'type' => 'website', // og:type, e.g. "article" for guides
    'noindex' => false,
])

@php
    $siteName = config('app.name');
    $fullTitle = $title ? "$title · $siteName" : $siteName;
    $description = Str::limit($description ?: config('site.description'), 160);
    $image = $image ?: config('site.share_image');
    $imageUrl = Str::startsWith($image, ['http://', 'https://']) ? $image : asset($image);
    $canonical = url()->current();
@endphp

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
@if ($noindex)
    <meta name="robots" content="noindex, follow">
@else
    <link rel="canonical" href="{{ $canonical }}">
@endif
<meta name="theme-color" content="#0f2a1b">

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title ?: $siteName }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $imageUrl }}">
<meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title ?: $siteName }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $imageUrl }}">
