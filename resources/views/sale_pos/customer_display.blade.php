<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#141210">
    <title>Customer display | {{ session('business.name', config('app.name')) }}</title>
    <link rel="stylesheet" href="{{ asset('css/customer-display.css?v=' . filemtime(public_path('css/customer-display.css'))) }}">
</head>
<body>
    <header class="display-header"><div class="brand"><span class="brand-icon" aria-hidden="true">&#9671;</span><div><strong>{{ session('business.name', config('app.name')) }}</strong><small id="display-location">BAR &amp; LOUNGE</small></div></div><div class="display-tools"><span id="connection-status" role="status">Waiting for register</span><button id="display-fullscreen" type="button" aria-label="Toggle full screen">&#x26F6; <span>Full screen</span></button></div></header>
    <main class="display-grid">
        <aside class="display-welcome"><span class="eyebrow">MAKE YOURSELF AT HOME</span><h1>Good drinks.<br>Great <em>company.</em></h1><p>Settle in. We&rsquo;ll take care of the rest.</p><img src="{{ asset('img/bar-cocktail.svg') }}" alt="Citrus cocktail in a coupe glass"><div class="welcome-note"><span>POURED WITH CARE</span><p>Thank you for spending your evening with us.</p></div></aside>
        <section class="order-panel"><div class="order-heading"><div><span class="eyebrow">FRESHLY ADDED, JUST FOR YOU</span><h2>Your order</h2></div><span id="item-count">0 items</span></div><div class="order-columns"><span>ITEM</span><span>QTY</span><span>AMOUNT</span></div><div class="order-scroll" id="order-items"></div><div id="empty-order" class="empty-order"><span aria-hidden="true">&#x2727;</span><h3>Your evening starts here.</h3><p>Your items will appear as we add them to your order.</p></div><div class="order-totals"><div><span>Subtotal</span><strong id="order-subtotal">&mdash;</strong></div><div id="discount-row" hidden><span>Discount</span><strong id="order-discount"></strong></div><div id="tax-row" hidden><span>Tax</span><strong id="order-tax"></strong></div><div id="adjustments-row" hidden><span>Charges &amp; rounding</span><strong id="order-adjustments"></strong></div><div class="grand-total"><div><span>Total to pay</span><small>Please check your order before payment.</small></div><strong id="order-total">&mdash;</strong></div></div><footer class="order-footer"><span>Made for moments worth savouring.</span><span>Enjoy responsibly.</span></footer></section>
    </main>
    <script src="{{ asset('js/customer-display.js?v=' . filemtime(public_path('js/customer-display.js'))) }}"></script>
</body>
</html>
