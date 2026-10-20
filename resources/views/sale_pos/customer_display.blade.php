<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="{{ $display_preset['theme'] }}">
    <title>Customer display | {{ $business->name }}</title>
    <link rel="stylesheet" href="{{ asset('css/customer-display.css?v=' . filemtime(public_path('css/customer-display.css'))) }}">
</head>
<body class="display-layout-{{ $pos_layout_key }}" data-context="{{ $display_preset['context'] }}">
    <header class="display-header">
        <div class="brand"><span class="brand-icon" aria-hidden="true">&#9671;</span><div><strong>{{ $business->name }}</strong><small id="display-location">{{ $display_preset['context'] }}</small></div></div>
        <div class="display-tools"><span id="connection-status" role="status">Waiting for register</span><button id="display-fullscreen" type="button" aria-label="Enter full screen">&#x26F6; <span>Full screen</span></button></div>
    </header>
    <main class="display-grid">
        <aside class="display-welcome">
            <span class="eyebrow">{{ $display_preset['eyebrow'] }}</span>
            <h1>{{ $display_preset['headline'] }}<br><em>{{ $display_preset['highlight'] }}</em></h1>
            <p>{{ $display_preset['intro'] }}</p>
            <img src="{{ asset('img/'.$display_preset['image']) }}" alt="{{ $display_preset['alt'] }}">
            <div class="welcome-note"><span>{{ $display_preset['note'] }}</span><p>{{ $display_preset['thanks'] }}</p></div>
        </aside>
        <section class="order-panel">
            <div class="order-heading"><div><span class="eyebrow">{{ $display_preset['order_eyebrow'] }}</span><h2>{{ $display_preset['order'] }}</h2></div><span id="item-count">0 items</span></div>
            <div class="order-columns"><span>ITEM</span><span>QTY</span><span>AMOUNT</span></div>
            <div class="order-scroll" id="order-items"></div>
            <div id="empty-order" class="empty-order"><span aria-hidden="true">&#x2727;</span><h3>{{ $display_preset['empty'] }}</h3><p>{{ $display_preset['hint'] }}</p></div>
            <div class="order-totals">
                <div><span>Subtotal</span><strong id="order-subtotal">&mdash;</strong></div>
                <div id="discount-row" hidden><span>Discount</span><strong id="order-discount"></strong></div>
                <div id="tax-row" hidden><span>Tax</span><strong id="order-tax"></strong></div>
                <div id="adjustments-row" hidden><span>Charges &amp; rounding</span><strong id="order-adjustments"></strong></div>
                <div class="grand-total"><div><span>Total to pay</span><small>{{ $display_preset['check'] }}</small></div><strong id="order-total">&mdash;</strong></div>
            </div>
            <footer class="order-footer"><span>{{ $display_preset['footer'] }}</span><span>{{ $display_preset['closing'] }}</span></footer>
        </section>
    </main>
    <script src="{{ asset('js/customer-display.js?v=' . filemtime(public_path('js/customer-display.js'))) }}"></script>
</body>
</html>
