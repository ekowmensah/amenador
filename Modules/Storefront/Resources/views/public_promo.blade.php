@if($placement === 'nav')
<a class="bar-button public-shop-nav" data-storefront-promo="nav" href="{{ $publicStoreUrl }}">Shop online &rarr;</a>
@elseif($placement === 'hero')
<div class="sf-public-promo" data-storefront-promo="hero"><a class="bar-button public-shop-hero" href="{{ $publicStoreUrl }}">Shop your favourites &rarr;</a><p>Good finds. Easy ordering.<span>Browse, add to cart, and checkout for {{ $publicStoreDelivery ? 'pickup or delivery' : 'store pickup' }}.</span></p></div>
@else
<aside class="sf-public-floating" data-storefront-promo="floating" aria-label="Online store"><span>Good finds. Easy ordering.</span><a href="{{ $publicStoreUrl }}">Shop online &rarr;</a></aside>
@endif
