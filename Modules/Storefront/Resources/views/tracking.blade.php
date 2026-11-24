@extends('storefront::layout')
@section('title', 'Track your order')
@push('styles')<link rel="stylesheet" href="{{ route('storefront.asset', 'checkout.css') }}?v=1.1.0">@endpush
@section('content')
<main class="pcp sf-checkout sf-receipt">
    <header class="sf-checkout-header"><a href="{{ route('storefront.index', $store) }}">{{ $store->name }}</a><span>Order tracking</span></header>
    <div class="sf-checkout-title"><span class="pcp-eyebrow">FROM OUR STORE TO YOU</span><h1>Where’s my order?</h1><p>Enter your order code and the phone number you used at checkout.</p></div>
    @if($errors->any())<div class="sf-alert" role="alert">{{ $errors->first() }}</div>@endif
    <section class="sf-panel"><form method="post" action="{{ route('storefront.track.lookup', $store) }}" id="sf-tracking-form">@csrf<div class="sf-fields"><label>Order code<input name="code" class="sf-code-input" placeholder="SF-7K3M-2Q8X" value="{{ old('code', $order ? ($order->tracking_code ?: $order->reference) : '') }}" maxlength="60" autocomplete="off" autocapitalize="characters" spellcheck="false" required></label><label>Phone number<input name="phone" type="tel" placeholder="Full number used at checkout" maxlength="40" autocomplete="tel" required></label></div><p class="sf-small">Your code is on your order confirmation. Your full order reference also works. Use the same phone number, including the country code if you entered one at checkout.</p><button class="sf-submit">{{ $order ? 'Track another order' : 'Track order' }}</button></form></section>
    @if($order)
    <section class="sf-panel sf-tracking-result" id="sf-tracking-result" data-status-url="{{ route('storefront.track.status', $store) }}" aria-labelledby="sf-tracking-heading">
        <div class="sf-tracking-top"><span class="pcp-eyebrow">YOUR ORDER</span><strong class="sf-tracking-code">{{ $progress['code'] }}</strong></div>
        <h2 id="sf-tracking-heading" data-tracking-heading>{{ $progress['heading'] }}</h2><p data-tracking-description>{{ $progress['description'] }}</p>
        <div class="sf-tracking-badges"><span>{{ $progress['fulfilment'] }}</span><span data-tracking-payment>{{ $progress['payment'] }}</span></div>
        <ol class="sf-tracking-steps" data-tracking-steps>@foreach($progress['steps'] as $step)<li class="sf-step-{{ $step['state'] }}" @if($step['state'] === 'current') aria-current="step" @endif><span class="sf-step-marker" aria-hidden="true">{{ $step['state'] === 'done' ? '✓' : $loop->iteration }}</span><span>{{ $step['label'] }}</span><small>{{ $step['state'] === 'done' ? 'Complete' : ($step['state'] === 'current' ? 'In progress' : 'Next') }}</small></li>@endforeach</ol>
        <div class="sf-tracking-bottom"><p class="sf-small" data-tracking-refresh role="status" aria-live="polite">Updates automatically every 30 seconds.</p><button class="sf-refresh" type="button" data-tracking-refresh-button>Refresh progress</button></div>
        <a class="sf-submit sf-secondary" href="{{ route('storefront.order', [$store, $order->token]) }}">View order details{{ $order->status === 'awaiting_payment' ? ' & payment' : '' }}</a>
    </section>
    @endif
    <p class="sf-small">Need help? Call <a href="tel:{{ preg_replace('/[^0-9+]/', '', $store->location->mobile ?? '') }}">{{ $store->location->mobile }}</a> and quote your order code.</p><a class="sf-back" href="{{ route('storefront.index', $store) }}">Back to the shop</a>
</main>
@endsection
@push('scripts')<script src="{{ route('storefront.asset', 'tracking.js') }}?v=1.1.0" defer></script>@endpush
