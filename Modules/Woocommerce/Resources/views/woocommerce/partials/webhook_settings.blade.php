<div class="pos-tab-content">
    <div class="row">
        <div class="col-xs-12">
            <h4>@lang('woocommerce::lang.product_updated')</h4>
            <p>@lang('woocommerce::lang.product_webhook_help')</p>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('product_webhook_secret', __('woocommerce::lang.webhook_secret') . ':') !!}
                {!! Form::text('product_webhook_secret', $default_settings['product_webhook_secret'] ?? null, ['class' => 'form-control', 'autocomplete' => 'off']); !!}
            </div>
        </div>
        <div class="col-xs-8">
            <strong>@lang('woocommerce::lang.webhook_delivery_url'):</strong>
            <p>{{ action([\Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'productUpdated'], ['business_id' => session()->get('business.id')]) }}</p>
        </div>
        <div class="col-xs-12">
            <div class="form-group">
                <strong>@lang('woocommerce::lang.product_import_fields'):</strong>
                @foreach(['name', 'description', 'image', 'weight', 'category', 'price'] as $field)
                    <label class="checkbox-inline">
                        {!! Form::checkbox('product_fields_for_import[]', $field, in_array($field, $default_settings['product_fields_for_import'] ?? ['name', 'description', 'image', 'weight', 'category', 'price'])) !!}
                        {{ ucfirst($field) }}
                    </label>
                @endforeach
            </div>
        </div>
        <div class="col-xs-12">
            <h4>@lang('woocommerce::lang.order_created')</h4>
        </div>
    	<div class="col-xs-4">
            <div class="form-group">
            	{!! Form::label('woocommerce_wh_oc_secret',  __('woocommerce::lang.webhook_secret') . ':') !!}
            	{!! Form::text('woocommerce_wh_oc_secret', !empty($business->woocommerce_wh_oc_secret) ? $business->woocommerce_wh_oc_secret : null, ['class' => 'form-control','placeholder' => __('woocommerce::lang.webhook_secret')]); !!}
            </div>
        </div>
        <div class="col-xs-8">
            <div class="form-group">
                <strong>@lang('woocommerce::lang.webhook_delivery_url'):</strong>
                <p>{{action([\Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'orderCreated'], ['business_id' => session()->get('business.id')])}}</p>
            </div>
        </div>

        <div class="col-xs-12">
            <h4>@lang('woocommerce::lang.order_updated')</h4>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('woocommerce_wh_ou_secret',  __('woocommerce::lang.webhook_secret') . ':') !!}
                {!! Form::text('woocommerce_wh_ou_secret', !empty($business->woocommerce_wh_oc_secret) ? $business->woocommerce_wh_ou_secret : null, ['class' => 'form-control','placeholder' => __('woocommerce::lang.webhook_secret')]); !!}
            </div>
        </div>
        <div class="col-xs-8">
            <div class="form-group">
                <strong>@lang('woocommerce::lang.webhook_delivery_url'):</strong>
                <p>{{action([\Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'orderUpdated'], ['business_id' => session()->get('business.id')])}}</p>
            </div>
        </div>

        <div class="col-xs-12">
            <h4>@lang('woocommerce::lang.order_deleted')</h4>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('woocommerce_wh_od_secret',  __('woocommerce::lang.webhook_secret') . ':') !!}
                {!! Form::text('woocommerce_wh_od_secret', !empty($business->woocommerce_wh_oc_secret) ? $business->woocommerce_wh_od_secret : null, ['class' => 'form-control','placeholder' => __('woocommerce::lang.webhook_secret')]); !!}
            </div>
        </div>
        <div class="col-xs-8">
            <div class="form-group">
                <strong>@lang('woocommerce::lang.webhook_delivery_url'):</strong>
                <p>{{action([\Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'orderDeleted'], ['business_id' => session()->get('business.id')])}}</p>
            </div>
        </div>

        <div class="col-xs-12">
            <h4>@lang('woocommerce::lang.order_restored')</h4>
        </div>
        <div class="col-xs-4">
            <div class="form-group">
                {!! Form::label('woocommerce_wh_or_secret',  __('woocommerce::lang.webhook_secret') . ':') !!}
                {!! Form::text('woocommerce_wh_or_secret', !empty($business->woocommerce_wh_oc_secret) ? $business->woocommerce_wh_or_secret : null, ['class' => 'form-control','placeholder' => __('woocommerce::lang.webhook_secret')]); !!}
            </div>
        </div>
        <div class="col-xs-8">
            <div class="form-group">
                <strong>@lang('woocommerce::lang.webhook_delivery_url'):</strong>
                <p>{{action([\Modules\Woocommerce\Http\Controllers\WoocommerceWebhookController::class, 'orderRestored'], ['business_id' => session()->get('business.id')])}}</p>
            </div>
        </div>

    </div>
</div>
