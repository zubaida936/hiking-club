@php($ga4 = config('site.analytics.ga4_id'))

@if ($ga4)
    {{-- Google Analytics 4. Only rendered when GOOGLE_ANALYTICS_ID is set. --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($ga4) }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @js($ga4));
    </script>
@endif
