{{-- "nav" marks a main menu item as active when the route alone does not tell (agrarian documents use the documents routes). --}}
{{-- "type" and "publishedAt" describe articles for social networks; "schema" adds the page's own structured data for Google. --}}
@props(['title' => null, 'description' => null, 'image' => null, 'robots' => null, 'nav' => null, 'type' => 'website', 'publishedAt' => null, 'schema' => null])

@php
    use App\Support\Seo\SearchEngines;
    use App\Support\Seo\StructuredData;
@endphp

@php
    $siteName = $settings['site_name'] ?? config('app.name');
    $metaDescription = $description ?: ($settings['site_description'] ?? '');
    $canonical = SearchEngines::canonicalUrl(request());
    $shareImage = $image ?: asset('images/FONDO.jpg');
    // A test or training copy is never indexed, whatever the page says.
    $robotsContent = ! SearchEngines::indexingAllowed() ? 'noindex, nofollow' : ($robots ?: 'index, follow, max-image-preview:large');
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | ' : '' }}{{ $siteName }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($metaDescription, 160) }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="robots" content="{{ $robotsContent }}">
    @if ($googleCode = SearchEngines::verificationCode('google_site_verification'))
        <meta name="google-site-verification" content="{{ $googleCode }}">
    @endif
    @if ($bingCode = SearchEngines::verificationCode('bing_site_verification'))
        <meta name="msvalidate.01" content="{{ $bingCode }}">
    @endif

    <meta property="og:type" content="{{ $type }}">
    <meta property="og:locale" content="es_PE">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title ?? $siteName }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($metaDescription, 200) }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $shareImage }}">
    @if ($type === 'article' && $publishedAt)
        <meta property="article:published_time" content="{{ $publishedAt->toIso8601String() }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? $siteName }}">
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit($metaDescription, 200) }}">
    <meta name="twitter:image" content="{{ $shareImage }}">
    <x-structured-data :data="[...StructuredData::site(), ...($schema ? [$schema] : [])]" />
    <meta name="theme-color" content="#0e3b25">
    @if (\App\Support\Analytics\PageViewRecorder::shouldTrack(request()))
        {{-- The portal's own statistics (resources/js/analytics.js): no cookies, no personal data; staff and robots are not counted. --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="analytics-endpoint" content="{{ route('analytics.store') }}">
    @endif

    @if (filled($settings['site_favicon'] ?? null))
        {{-- Icon uploaded in «Datos del portal»; one square image serves the tab and the phone shortcut. --}}
        <link rel="icon" href="{{ \App\Models\Setting::faviconUrl() }}">
        <link rel="apple-touch-icon" href="{{ \App\Models\Setting::faviconUrl() }}">
    @else
        <link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
        <link rel="icon" href="{{ asset('icon-192.png') }}" type="image/png" sizes="192x192">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    @endif
    <script>
        {{-- Applies the visitor's saved accessibility preferences before the first paint. --}}
        window.applyAccessibility = function (settings) {
            var root = document.documentElement;
            var classes = { contrast: 'a11y-contrast', grayscale: 'a11y-grayscale', links: 'a11y-links', dyslexia: 'a11y-dyslexia', noMotion: 'a11y-no-motion' };
            settings = settings || {};
            root.style.fontSize = settings.fontScale && settings.fontScale !== 100 ? settings.fontScale + '%' : '';
            Object.keys(classes).forEach(function (key) { root.classList.toggle(classes[key], !! settings[key]); });
        };
        try { window.applyAccessibility(JSON.parse(window.localStorage.getItem('dramdd:accesibilidad'))); } catch (error) {}
        {{-- Hides reveal-on-scroll blocks only when motion is allowed; if app.js never runs, they show after 3 s. --}}
        (function (root) {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || root.classList.contains('a11y-no-motion')) { return; }
            root.classList.add('js-motion');
            setTimeout(function () { if (! window.__motionReady) { root.classList.remove('js-motion'); } }, 3000);
        })(document.documentElement);
    </script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#contenido" class="sr-only z-60 print:hidden rounded-lg bg-gold-400 px-4 py-2 font-semibold text-brand-950 shadow-lg focus:not-sr-only focus:fixed focus:top-3 focus:left-3">
        Saltar al contenido principal
    </a>

    <x-site.header :active="$nav" />

    <main id="contenido" class="flex-1 focus:outline-none" tabindex="-1">
        {{ $slot }}
    </main>

    <x-site.footer />

    <x-site.accessibility />

    {{-- Pages kept out of search engines (errors, receipts, the citizen's account) are utilities: no announcements there. --}}
    @if (blank($robots))
        <x-site.announcements />
    @endif

    <button type="button" data-back-to-top
        class="pointer-events-none invisible fixed right-4 bottom-4 z-30 flex size-12 translate-y-3 items-center justify-center rounded-full bg-brand-900 text-white opacity-0 shadow-lg ring-1 ring-white/20 transition duration-300 ease-out-quint hover:bg-brand-800 data-visible:pointer-events-auto data-visible:visible data-visible:translate-y-0 data-visible:opacity-100 sm:right-6 sm:bottom-6 print:hidden"
        aria-label="Volver al inicio de la página">
        <x-heroicon-o-arrow-up class="size-5" />
    </button>
</body>
</html>
