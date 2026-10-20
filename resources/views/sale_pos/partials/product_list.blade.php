@forelse($products as $product)
	@php
		$defaultProductImage = asset('/img/default.png') . '?v=' . filemtime(public_path('img/default.png'));
		$productImage = null;
		if (count($product->media) > 0) {
			$productImage = $product->media->first()->display_url;
		} elseif (!empty($product->product_image)) {
			$productImage = asset('/uploads/img/' . rawurlencode($product->product_image));
		}
	@endphp
	<div class="col-md-3 col-xs-4 product_list no-print" data-stock-status="@if($product->enable_stock && $product->qty_available <= 0) out_of_stock @elseif($product->enable_stock && $product->qty_available <= ($product->alert_quantity ?? 5)) low_stock @else in_stock @endif">
		<div class="product_box pos-modern-product"
			data-variation_id="{{$product->id}}"
			data-product_name="{{$product->name}}"
			data-variation_name="{{$product->variation}}"
			data-sub_sku="{{$product->sub_sku}}"
			data-selling_price="{{$product->selling_price}}"
			data-qty_available="{{$product->qty_available}}"
			data-enable_stock="{{$product->enable_stock}}"
			data-unit="{{$product->unit}}"
			title="{{$product->name}} @if($product->type == 'variable')- {{$product->variation}} @endif {{ '(' . $product->sub_sku . ')'}} @if(!empty($show_prices)) @lang('lang_v1.default') - @format_currency($product->selling_price) @foreach($product->group_prices as $group_price) @if(array_key_exists($group_price->price_group_id, $allowed_group_prices)) {{$allowed_group_prices[$group_price->price_group_id]}} - @format_currency($group_price->price_inc_tax) @endif @endforeach @endif">
		<span class="pos-product-stock @if($product->enable_stock && $product->qty_available <= 0) is-empty @elseif($product->enable_stock && $product->qty_available <= ($product->alert_quantity ?? 5)) is-low @endif">
			@if(!$product->enable_stock) Available @elseif($product->qty_available > 0) {{ number_format($product->qty_available, 2) }} in stock @else Out of stock @endif
		</span>

		<div class="image-container" 
			style="background-image: @if($productImage) url('{{ $productImage }}'), @endif url('{{ $defaultProductImage }}');
			background-repeat: no-repeat; background-position: center;
			background-size: contain;">
			
		</div>

		<div class="text_div pos-product-copy">
			<strong class="pos-product-name">{{$product->name}}
			@if($product->type == 'variable')
				- {{$product->variation}}
			@endif
			</strong>
			<div class="pos-product-meta">
				<span class="pos-product-sku">SKU: {{$product->sub_sku}}</span>
				<span class="pos-product-price">@format_currency($product->selling_price)</span>
			</div>
		</div>
			
		</div>
	</div>
@empty
	<input type="hidden" id="no_products_found">
	<div class="col-md-12 pos-product-empty">
		<i class="fas fa-box-open"></i>
		<h4>@lang('lang_v1.no_products_to_display')</h4>
		<p>Try another category, brand or search term.</p>
	</div>
@endforelse
