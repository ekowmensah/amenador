<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Good drinks, good company and a place to unwind. Discover our bar and lounge.">
    <meta name="theme-color" content="#141210">
    <title>{{ config('app.name') }} | Bar &amp; Lounge</title>
    <link rel="stylesheet" href="{{ asset('css/bar-pages.css?v=' . filemtime(public_path('css/bar-pages.css'))) }}">
</head>
<body class="bar-site">
<a href="#main" class="bar-eyebrow" style="position:absolute;left:18px;top:-100px" onfocus="this.style.top='8px'" onblur="this.style.top='-100px'">Skip to content</a>
<div class="bar-wrap">
    <header class="bar-nav">
        <a class="bar-logo" href="{{ url('/') }}">{{ config('app.name') }}<small>BAR &amp; LOUNGE</small></a>
        <nav class="bar-nav-links" aria-label="Main navigation"><a href="#experience">The experience</a><a href="#drinks">Our pours</a><a class="bar-button outline" href="{{ route('login') }}">Staff sign in &rarr;</a></nav>
    </header>
    <main id="main">
        <section class="bar-hero">
            <div><span class="bar-eyebrow">GOOD DRINKS. BETTER COMPANY.</span><h1>A little spirit.<br>A lot of <em>soul.</em></h1><p class="bar-lead">Settle in, raise a glass and let the evening unfold. Your favourite drinks, warm hospitality and a place to make the night your own.</p><div class="bar-actions"><a class="bar-button" href="#drinks">Find your next pour &rarr;</a><a class="bar-button outline" href="#experience">Discover the bar</a></div></div>
            <div class="bar-art"><img src="{{ asset('img/bar-cocktail.svg') }}" alt="An amber cocktail in a coupe glass with a citrus garnish"><span class="bar-art-caption">Made for the moments worth savouring</span></div>
        </section>
        <div class="bar-strip"><span>Perfectly poured</span><span>Easy conversation</span><span>Evenings well spent</span></div>
        <section class="bar-section" id="drinks"><span class="bar-eyebrow">SOMETHING FOR EVERY MOOD</span><h2>Find your kind of drink.</h2><p class="bar-section-intro">From a familiar favourite to something a little different, ask the team what is pouring behind the bar.</p><div class="bar-cards"><article class="bar-card"><span>01 / MIXED &amp; GARNISHED</span><h3>Cocktail hour</h3><p>Bright citrus, bold spirits and a finishing touch. Tell us what you like and let the conversation start there.</p></article><article class="bar-card"><span>02 / CRISP &amp; REFRESHING</span><h3>Keep it chilled</h3><p>Take it easy with a cold beer or a refreshing mixer. Sometimes your usual is exactly what the evening calls for.</p></article><article class="bar-card"><span>03 / SLOW &amp; SMOOTH</span><h3>Sip &amp; savour</h3><p>A spirit over ice, a glass of wine or an alcohol-free option. Take your time and enjoy it your way.</p></article></div></section>
        <section class="bar-section" id="experience" style="padding-top:0"><div class="bar-callout"><div><span class="bar-eyebrow">PULL UP A CHAIR</span><h2>Come for a drink.<br>Stay for the atmosphere.</h2><p>Catch up with your people, celebrate the little things<br>or simply unwind at the end of the day.</p></div><a class="bar-button outline" href="#drinks">Explore our pours &uarr;</a></div></section>
    </main>
    <footer class="bar-footer"><span>&copy; {{ date('Y') }} {{ config('app.name') }} &middot; Bar &amp; Lounge</span><span>Please drink responsibly. Adults only.</span><a href="{{ route('login') }}">Staff portal &rarr;</a></footer>
</div>
</body>
</html>
