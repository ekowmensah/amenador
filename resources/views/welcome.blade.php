<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $pagePreset['description'] }}">
    <meta name="theme-color" content="{{ $pagePreset['theme'] }}">
    <title>{{ $publicBusinessName }} | {{ $pagePreset['name'] }}</title>
    <link rel="stylesheet" href="{{ asset('css/bar-pages.css?v=' . filemtime(public_path('css/bar-pages.css'))) }}">
    <link rel="stylesheet" href="{{ asset('css/public-page-layouts.css?v=' . filemtime(public_path('css/public-page-layouts.css'))) }}">
</head>
<body class="bar-site public-layout-{{ $pageLayout }}">
<a href="#main" class="bar-eyebrow public-skip-link">Skip to content</a>
<div class="bar-wrap">
    <header class="bar-nav">
        <a class="bar-logo" href="{{ url('/') }}">{{ $publicBusinessName }}<small>{{ $pagePreset['name'] }}</small></a>
        <nav class="bar-nav-links" aria-label="Main navigation"><a href="#experience">About us</a><a href="#discover">{{ $pagePreset['nav'] }}</a><a class="bar-button outline" href="{{ route('login') }}">Staff sign in &rarr;</a></nav>
    </header>
    <main id="main">
        <section class="bar-hero">
            <div><span class="bar-eyebrow">{{ $pagePreset['eyebrow'] }}</span><h1>{{ $pagePreset['title'] }}<br><em>{{ $pagePreset['emphasis'] }}</em></h1><p class="bar-lead">{{ $pagePreset['lead'] }}</p><div class="bar-actions"><a class="bar-button" href="#discover">{{ $pagePreset['primary'] }} &rarr;</a><a class="bar-button outline" href="#experience">{{ $pagePreset['secondary'] }}</a></div></div>
            <div class="bar-art"><img src="{{ asset('img/'.$pagePreset['image']) }}" alt="{{ $pagePreset['alt'] }}"><span class="bar-art-caption">{{ $pagePreset['caption'] }}</span></div>
        </section>
        <div class="bar-strip">@foreach ($pagePreset['highlights'] as $highlight)<span>{{ $highlight }}</span>@endforeach</div>
        <section class="bar-section" id="discover">
            <span class="bar-eyebrow">{{ $pagePreset['section_eyebrow'] }}</span><h2>{{ $pagePreset['section_title'] }}</h2><p class="bar-section-intro">{{ $pagePreset['section_intro'] }}</p>
            <div class="bar-cards">@foreach ($pagePreset['cards'] as $card)<article class="bar-card"><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} / {{ $card['eyebrow'] }}</span><h3>{{ $card['title'] }}</h3><p>{{ $card['text'] }}</p></article>@endforeach</div>
        </section>
        <section class="bar-section public-experience" id="experience"><div class="bar-callout"><div><span class="bar-eyebrow">{{ $pagePreset['callout_eyebrow'] }}</span><h2>{{ $pagePreset['callout_title'] }}<br>{{ $pagePreset['callout_emphasis'] }}</h2><p>{{ $pagePreset['callout_text'] }}</p></div><a class="bar-button outline" href="#discover">{{ $pagePreset['primary'] }} &uarr;</a></div></section>
    </main>
    <footer class="bar-footer"><span>&copy; {{ date('Y') }} {{ $publicBusinessName }} &middot; {{ $pagePreset['name'] }}</span><span>{{ $pagePreset['closing'] }}</span><a href="{{ route('login') }}">Staff portal &rarr;</a></footer>
</div>
</body>
</html>
