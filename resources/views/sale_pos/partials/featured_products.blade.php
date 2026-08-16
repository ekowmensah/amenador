@foreach($featured_products as $variation)
	@php
		$defaultProductImage = asset('/img/default.png') . '?v=' . filemtime(public_path('img/default.png'));
		$featuredImage = count($variation->media) > 0
			? $variation->media->first()->display_url
			: (!empty($variation->product->image) ? asset('/uploads/img/' . rawurlencode($variation->product->image)) : null);
	@endphp
	<div class="col-md-3 col-xs-4 product_list no-print">
		<div class="product_box hover:tw-shadow-lg hover:tw-animate-pulse" data-toggle="tooltip" data-placement="bottom" data-variation_id="{{$variation->id}}" title="{{$variation->full_name}}">

		<div class="image-container" 
			style="background-image: @if($featuredImage) url('{{ $featuredImage }}'), @endif url('{{ $defaultProductImage }}');
			background-repeat: no-repeat; background-position: center;
			background-size: contain;">
			
		</div>

		<div class="text_div">
			<small class="text text-muted">{{$variation->product->name}} 
			@if($variation->product->type == 'variable')
				- {{$variation->name}}
			@endif
			</small>

			<small class="text-muted">
				({{$variation->sub_sku}})
			</small>
		</div>
			
		</div>
	</div>
@endforeach
