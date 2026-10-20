@extends('layouts.app')

@section('title', __('sale.pos_sale'))

@section('content')
    @php
        $pos_layout_key = $business_details->common_settings['pos_layout'] ?? 'restaurant';
        if (!array_key_exists($pos_layout_key, config('pos_layouts'))) {
            $pos_layout_key = 'restaurant';
        }
        $pos_preset = config('pos_layouts.'.$pos_layout_key);
    @endphp
    <section class="content no-print pos-v2-shell">
        <input type="hidden" id="customer-display-auto-open" value="{{ (bool) ($business_details->common_settings['auto_open_customer_display'] ?? true) ? '1' : '0' }}">
        <input type="hidden" id="amount_rounding_method" value="{{ $pos_settings['amount_rounding_method'] ?? '' }}">

        @if (!empty($pos_settings['allow_overselling']))
            <input type="hidden" id="is_overselling_allowed">
        @endif

        @if (session('business.enable_rp') == 1)
            <input type="hidden" id="reward_point_enabled">
        @endif

        @if (session('business.enable_lot_number') == 1 || session('business.enable_product_expiry') == 1)
            <input type="hidden" id="pos_lot_expiry_enabled" value="1">
        @endif

        @php
            $is_discount_enabled = $pos_settings['disable_discount'] != 1;
            $is_rp_enabled = session('business.enable_rp') == 1;
            $show_product_browser = empty($pos_settings['hide_product_suggestion']);
        @endphp

        {!! Form::open([
            'url' => action([\App\Http\Controllers\SellPosController::class, 'store']),
            'method' => 'post',
            'id' => 'add_pos_sell_form',
        ]) !!}

        @if (!$show_product_browser)
            <div class="pos-customer-search-bar"></div>
        @endif
        <div class="pos-v2-workspace @if (!$show_product_browser) pos-v2-workspace--cart-only @endif">
            <section class="pos-v2-panel pos-v2-cart-panel" aria-label="Current sale">
                <header class="hospitality-ticket-heading">
                    <div><span>{{ $pos_preset['ticket'] }}</span><h2>{{ $pos_preset['heading'] }}</h2></div>
                    <span class="hospitality-item-count" aria-live="polite">0 items</span>
                </header>
                <div class="pos-v2-cart-body">
                    <div class="box-body pb-0">
                        {!! Form::hidden('location_id', $default_location->id ?? null, [
                            'id' => 'location_id',
                            'data-receipt_printer_type' => !empty($default_location->receipt_printer_type)
                                ? $default_location->receipt_printer_type
                                : 'browser',
                            'data-default_payment_accounts' => $default_location->default_payment_accounts ?? '',
                        ]) !!}

                        {!! Form::hidden('sub_type', isset($sub_type) ? $sub_type : null) !!}
                        <input type="hidden" id="item_addition_method" value="{{ $business_details->item_addition_method }}">

                        @include('sale_pos.partials.pos_form')
                        @include('sale_pos.partials.pos_form_totals')
                        @include('sale_pos.partials.payment_modal')

                        @if (empty($pos_settings['disable_suspend']))
                            @include('sale_pos.partials.suspend_note_modal')
                        @endif

                        @if (empty($pos_settings['disable_recurring_invoice']))
                            @include('sale_pos.partials.recurring_invoice_modal')
                        @endif
                    </div>
                </div>
            </section>

            @if ($show_product_browser)
                <aside class="pos-v2-panel pos-v2-catalog-panel is-mobile-catalogue-collapsed" aria-label="Product browser">
                    @include('sale_pos.partials.pos_sidebar')
                </aside>
            @endif
        </div>

        @include('sale_pos.partials.pos_form_actions')
        {!! Form::close() !!}
    </section>

    <div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        @include('contact.create', ['quick_add' => true])
    </div>

    <div class="modal fade register_details_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade close_register_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>
    <div class="modal fade" id="expense_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

    @include('sale_pos.partials.configure_search_modal')
    @include('sale_pos.partials.recent_transactions_modal')
    @include('sale_pos.partials.weighing_scale_modal')
@stop

@section('css')
    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_css_path']))
                @includeIf($value['module_css_path'])
            @endif
        @endforeach
    @endif
@stop

@section('javascript')
    <script src="{{ asset('js/pos.js?v=' . filemtime(public_path('js/pos.js'))) }}"></script>
    <script src="{{ asset('js/customer-display-pos.js?v=' . filemtime(public_path('js/customer-display-pos.js'))) }}"></script>
    <script src="{{ asset('js/pos-hospitality.js?v=' . filemtime(public_path('js/pos-hospitality.js'))) }}"></script>
    <script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    @include('sale_pos.partials.keyboard_shortcuts')

    @if (in_array('tables', $enabled_modules) ||
            in_array('modifiers', $enabled_modules) ||
            in_array('service_staff', $enabled_modules))
        <script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif

    @if (!empty($pos_module_data))
        @foreach ($pos_module_data as $key => $value)
            @if (!empty($value['module_js_path']))
                @includeIf($value['module_js_path'], ['view_data' => $value['view_data']])
            @endif
        @endforeach
    @endif
@endsection
