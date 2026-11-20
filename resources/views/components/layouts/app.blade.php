@props(['title' => null, 'description' => null, 'image' => null, 'robots' => null])

@php
    $siteName = $settings['site_name'] ?? config('app.name');
    $metaDescription = $description ?: ($settings['site_description'] ?? '');
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | ' : '' }}{{ $siteName }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($metaDescription, 160) }}">
    <link rel="canonical" href="{{ url()->current() }}">
    @if ($robots)
        <meta name="robots" content="{{ $robots }}">
    @endif

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title ?? $siteName }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($metaDescription, 200) }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($image)
        <meta property="og:image" content="{{ $image }}">
    @endif
    <meta name="theme-color" content="#154f31">

    <link rel="icon" href="{{ asset('favicon-32.png') }}" type="image/png" sizes="32x32">
    <link rel="icon" href="{{ asset('icon-192.png') }}" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
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
    </script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#contenido" class="sr-only z-50 print:hidden rounded-md bg-gold-400 px-4 py-2 font-semibold text-brand-950 focus:not-sr-only focus:fixed focus:top-3 focus:left-3">
        Saltar al contenido principal
    </a>

    <x-site.header />

    <main id="contenido" class="flex-1" tabindex="-1">
        {{ $slot }}
    </main>

    <x-site.footer />

    <x-site.accessibility />
</body>
</html>
