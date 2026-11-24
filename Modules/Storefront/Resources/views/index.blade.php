@extends('storefront::layout')
@section('title', 'Order online')
@section('content')
@php
    $bag = '<svg class="pcp-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 7h14l1 14H4L5 7ZM8 8V6a4 4 0 0 1 8 0v2"/></svg>';
    $search = '<svg class="pcp-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg>';
    $plus = '<svg class="pcp-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>';
    $close = '<svg class="pcp-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>';
    $zero = $catalog->money($store, 0);
@endphp
<div class="pcp" id="pcp-app">
    <a class="pcp-skip" href="#pcp-menu">Skip to products</a>
    <header class="pcp-header">
        <a class="pcp-brand" href="{{ route('storefront.index', $store) }}"><span class="pcp-brand-mark">{!! $bag !!}</span><span><strong>{{ $store->name }}</strong><small>{{ $pagePreset['name'] }}</small></span></a>
        <nav class="pcp-nav" aria-label="Store navigation"><a class="pcp-nav-active" href="#pcp-menu">Our collection</a><a href="{{ route('storefront.track', $store) }}">Track order</a><a href="{{ route('storefront.checkout', $store) }}">Checkout</a></nav>
        <button class="pcp-header-cart" data-open-cart aria-controls="pcp-order" aria-expanded="false">{!! $bag !!}<span>My order</span><b data-cart-count>0</b></button>
    </header>
    <main class="pcp-layout">
        <section class="pcp-catalog" aria-label="Product collection">
            <div class="pcp-intro"><div><span class="pcp-eyebrow">{{ $pagePreset['eyebrow'] }}</span><h1>Welcome to {{ $store->name }}.</h1><p>{{ $pagePreset['caption'] }}</p></div></div>
            @if(session('storefront_error'))<div class="pcp-noscript" role="alert">{{ session('storefront_error') }}</div>@endif
            <section class="pcp-hero" aria-labelledby="pcp-hero-title"><div class="pcp-hero-copy"><span class="pcp-hero-label"><span></span> {{ $pagePreset['eyebrow'] }}</span><h2 id="pcp-hero-title">{{ $pagePreset['title'] }}<br>{{ $pagePreset['emphasis'] }}</h2><p>{{ $pagePreset['description'] }}</p><a class="pcp-hero-button" href="#pcp-menu">{{ $pagePreset['primary'] }} {!! $bag !!}</a></div><div class="pcp-hero-art"><div class="pcp-orbit pcp-orbit-one" aria-hidden="true"></div><div class="pcp-orbit pcp-orbit-two" aria-hidden="true"></div><img src="{{ $storefrontArt }}" alt="{{ $pagePreset['alt'] }}" fetchpriority="high"></div></section>
            <div class="pcp-benefits"><span>✓ Shop directly with us</span><span>{!! $bag !!} Pickup{{ $store->deliveryAvailable() ? ' & delivery' : ' at our store' }}</span><span>✓ Easy checkout</span></div>
            <section class="pcp-menu" id="pcp-menu" aria-labelledby="pcp-menu-title">
                <div class="pcp-section-heading"><div><span class="pcp-eyebrow">{{ $pagePreset['section_eyebrow'] }}</span><h2 id="pcp-menu-title">{{ $pagePreset['section_title'] }}</h2></div><span class="pcp-product-count" data-result-count>{{ $products->count() }} items</span></div>
                <div class="pcp-categories" role="group" aria-label="Filter by category"><button class="is-active" data-category="all" aria-pressed="true">All items</button>@foreach($categories as $category)<button data-category="{{ $category['id'] }}" aria-pressed="false">{{ ucfirst($category['name']) }}</button>@endforeach</div>
                <div class="pcp-toolbar"><label class="pcp-search">{!! $search !!}<input type="search" id="pcp-search" placeholder="Search your favourites…" aria-label="Search products" autocomplete="off"><kbd>/</kbd></label><label class="pcp-sort"><span class="pcp-sr-only">Sort products</span><select id="pcp-sort"><option value="name">Name: A–Z</option><option value="price-low">Price: low to high</option><option value="price-high">Price: high to low</option></select></label></div>
                <div class="pcp-products" id="pcp-products">
                @foreach($products as $product)
                    <article class="pcp-product" data-id="{{ $product['id'] }}" data-name="{{ $product['name'] }}" data-price="{{ $product['price_minor'] / 100 }}" data-categories="{{ $product['category_id'] }} {{ $product['subcategory_id'] }}">
                        <div class="pcp-product-image pcp-tone-{{ $loop->index % 4 }}"><img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" loading="lazy">@unless($product['available'])<span class="pcp-product-badge pcp-sold-out">Sold out</span>@endunless<span class="pcp-in-cart" data-in-cart="{{ $product['id'] }}" hidden></span></div>
                        <div class="pcp-product-info"><span class="pcp-product-category">{{ $product['category'] }}</span><h3>{{ mb_convert_case($product['name'], MB_CASE_TITLE, 'UTF-8') }}</h3><div class="pcp-product-bottom"><span class="pcp-price">{{ $catalog->money($store, $product['price_minor']) }}</span><button class="pcp-add" data-add="{{ $product['id'] }}" aria-label="Add {{ $product['name'] }} to order" @disabled(!$product['available'])>{!! $plus !!}</button></div></div>
                    </article>
                @endforeach
                </div>
                <div class="pcp-no-results" data-no-results @if($products->isNotEmpty()) hidden @endif>{!! $search !!}<h3>No items found</h3><p>Try another search or browse a different category.</p><button class="pcp-text-button" data-reset-filters>Show all items</button></div>
            </section>
            <footer class="pcp-footer"><span>{{ $store->name }} · {{ $store->location->city }}</span><a href="{{ route('storefront.track', $store) }}">Track your order</a><a href="tel:{{ preg_replace('/[^0-9+]/', '', $store->location->mobile ?? '') }}">{{ $store->location->mobile }}</a></footer>
        </section>
        <div class="pcp-cart-overlay" data-close-cart hidden></div>
        <aside class="pcp-order" id="pcp-order" aria-labelledby="pcp-order-title" tabindex="-1">
            <div class="pcp-order-top"><span class="pcp-order-icon">{!! $bag !!}</span><div><h2 id="pcp-order-title">Your order <span data-cart-count>0</span></h2><p>Your next favourite is a few taps away.</p></div><button class="pcp-close-cart" data-close-cart aria-label="Close order">{!! $close !!}</button></div>
            <div class="pcp-order-contents" data-cart-items><div class="pcp-cart-empty"><div class="pcp-empty-art">{!! $bag !!}<span>+</span></div><h3>Good things<br>are waiting.</h3><p>Add something from the collection<br>to get your order started.</p><button class="pcp-empty-button" data-browse-menu>Let’s find your favourites {!! $bag !!}</button></div></div>
            <div class="pcp-order-bottom"><div class="pcp-totals-row"><span>Subtotal</span><strong data-subtotal>{{ $zero }}</strong></div><div class="pcp-totals-row pcp-discount" data-discount-row hidden><span>Discount</span><strong data-discount></strong></div><p class="pcp-checkout-note">Prices include item taxes. Delivery charges, where available, are shown at checkout.</p><div class="pcp-totals-row pcp-total"><span>Subtotal</span><strong data-total>{{ $zero }}</strong></div><a class="pcp-checkout is-disabled" data-checkout href="{{ route('storefront.checkout', $store) }}" aria-disabled="true"><span>Continue to checkout</span>{!! $bag !!}</a><span class="pcp-safe-note">Order directly from {{ $store->name }}</span></div>
            <div class="pcp-order-footnote">{!! $bag !!}<span>Picked by you.<br><strong>Prepared with care.</strong></span></div>
        </aside>
    </main>
    <button class="pcp-mobile-cart" data-open-cart aria-controls="pcp-order" aria-expanded="false">{!! $bag !!}<span>View your order <b data-cart-count>0</b></span><strong data-mobile-total>{{ $zero }}</strong></button>
    <div class="pcp-toast" role="status" aria-live="polite" hidden></div>
    <noscript><div class="pcp-noscript">Enable JavaScript to build your order, or call {{ $store->location->mobile }} for help.</div></noscript>
</div>
@endsection
@push('scripts')
@php $clientConfig = ['endpoint' => route('storefront.cart', $store), 'csrf' => csrf_token(), 'checkout' => route('storefront.checkout', $store)]; @endphp
<script>window.perryPOS = @json($clientConfig);</script>
<script src="{{ route('storefront.asset', 'store.js') }}?v=1.0.0" defer></script>
@endpush
