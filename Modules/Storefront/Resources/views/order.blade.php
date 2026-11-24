@extends('storefront::layout')
@section('title', 'Your order')
@push('styles')<link rel="stylesheet" href="{{ route('storefront.asset', 'checkout.css') }}?v=1.1.0">@endpush
@section('content')
@php $money = function ($minor) use ($order) { return $order->currency.' '.number_format($minor / 100, 2); }; @endphp
<main class="pcp sf-checkout sf-receipt">
    <header class="sf-checkout-header"><a href="{{ route('storefront.index', $store) }}">{{ $store->name }}</a><span>Your order</span></header>
    @if(session('payment_error'))<div class="sf-alert" role="alert">{{ session('payment_error') }}</div>@endif
    <section class="sf-receipt-status">
        <span class="pcp-eyebrow">{{ strtoupper(str_replace('_', ' ', $order->fulfilment_status)) }}</span>
        @if($order->status === 'paid')<h1>Payment received. Thank you!</h1><p>Your order is in the system and the store can begin preparing it.</p>
        @elseif($order->status === 'placed')<h1>You’re all set.</h1><p>Your order has been placed. Payment is due when you {{ $order->fulfilment === 'delivery' ? 'receive your delivery' : 'collect it from the store' }}.</p>
        @elseif($order->status === 'paid_review')<h1>Payment received. We’re checking your order.</h1><p>The store needs to confirm stock or complete processing. Please contact us with the reference below. Do not make another payment for this order.</p>
        @else<h1>Your order is saved.</h1><p>Complete payment to send your order to the store. Availability will be checked again after payment.</p>@endif
        <span class="sf-receipt-reference">{{ $order->reference }}</span>
        <div class="sf-tracking-confirmation"><span>Your tracking code</span><strong class="sf-tracking-code">{{ $order->tracking_code ?: $order->reference }}</strong><div class="sf-tracking-links"><button type="button" class="sf-refresh" data-copy-tracking="{{ $order->tracking_code ?: $order->reference }}">Copy code</button><a class="sf-refresh" href="{{ route('storefront.track', $store) }}">Track your order</a></div><small>Use this code and your checkout phone number to check progress.</small><span data-copy-status role="status" aria-live="polite"></span></div>
        @if($order->status === 'awaiting_payment')<div class="sf-payment-actions"><form method="post" action="{{ route('storefront.pay', [$store, $order->token]) }}">@csrf<button class="sf-submit">Pay {{ $money($order->total_minor) }} with Paystack</button></form><form method="post" action="{{ route('storefront.verify', [$store, $order->token]) }}">@csrf<button class="sf-submit sf-secondary">Check payment status</button></form></div>@endif
    </section>
    <section class="sf-panel sf-summary"><h2>Order summary</h2>@foreach($order->items as $item)<div class="sf-summary-item"><img src="{{ $item['image'] }}" alt=""><div><strong>{{ $item['name'] }}</strong><small>Qty {{ $item['quantity'] }}</small></div><b>{{ $money($item['price_minor'] * $item['quantity']) }}</b></div>@endforeach<div class="sf-summary-total"><span>Subtotal</span><strong>{{ $money($order->subtotal_minor) }}</strong></div><div class="sf-summary-total"><span>Delivery</span><strong>{{ $money($order->delivery_minor) }}</strong></div><div class="sf-summary-total sf-grand-total"><span>Total</span><strong>{{ $money($order->total_minor) }}</strong></div></section>
    <section class="sf-panel"><h2>{{ $order->fulfilment === 'delivery' ? 'Delivery details' : 'Pickup details' }}</h2><div class="sf-receipt-meta"><div><span>Customer</span>{{ $order->customer['name'] }}<br>{{ $order->customer['phone'] }}</div><div><span>{{ $order->fulfilment === 'delivery' ? 'Deliver to' : 'Collect from' }}</span>@if($order->fulfilment === 'delivery'){{ $order->customer['address'] }}<br>{{ $order->customer['city'] }}@else{{ $store->location->name }}<br>{{ $store->location->city }}@endif</div></div><p class="sf-small">Need help? Call <a href="tel:{{ preg_replace('/[^0-9+]/', '', $store->location->mobile ?? '') }}">{{ $store->location->mobile }}</a> and quote your tracking code. You can check progress any time from <a href="{{ route('storefront.track', $store) }}">Track your order</a>.</p></section>
    <a class="sf-back" href="{{ route('storefront.index', $store) }}">Continue shopping</a>
</main>
@endsection
@push('scripts')<script src="{{ route('storefront.asset', 'tracking.js') }}?v=1.1.0" defer></script>@endpush
