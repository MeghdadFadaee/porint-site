<!doctype html>
<html lang="{{ $locale }}" dir="{{ $locale === 'fa' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · PORINT</title>
    <meta name="description" content="{{ $description }}">
    @if ($draft ?? false)
        <meta name="robots" content="noindex, nofollow">
    @else
        <link rel="canonical" href="{{ $alternates[$locale] }}">
        @foreach ($alternates as $language => $url)
            <link rel="alternate" hreflang="{{ $language }}" href="{{ $url }}">
        @endforeach
    @endif
    @stack('head')
    <link rel="preload" href="{{ asset('fonts/vazirmatn.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('fonts/studio-font.css') }}">
    <link rel="stylesheet" href="{{ asset('porint/wordmark.css') }}">
    <link rel="icon" href="{{ asset('porint/favicon.png') }}" type="image/png">
    @vite(['resources/css/studio.css', 'resources/js/preview.ts'])
</head>
<body>
    <a class="skip-link" href="#content">{{ $locale === 'fa' ? 'رفتن به محتوا' : 'Skip to content' }}</a>
    <header class="site-header">
        <div class="shell masthead">
            <a class="brand porint-wordmark" href="{{ route($locale.'.home') }}" aria-label="PORINT · {{ __('studio.home') }}">PORINT</a>
            <nav aria-label="{{ $locale === 'fa' ? 'منوی اصلی' : 'Main menu' }}">
                <a href="{{ route($locale.'.catalog') }}">{{ __('studio.catalog') }}</a>
                <a href="{{ route($locale.'.guides') }}">{{ __('studio.guides') }}</a>
            </nav>
            <a class="language" lang="{{ $alternateLocale }}" hreflang="{{ $alternateLocale }}" href="{{ $alternates[$alternateLocale] }}">{{ __('studio.language') }}</a>
        </div>
    </header>
    <main id="content">
        @yield('content')
    </main>
    @include('studio.footer')
</body>
</html>
