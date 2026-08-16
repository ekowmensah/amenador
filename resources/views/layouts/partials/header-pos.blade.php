@php
    $go_back_url = action([\App\Http\Controllers\SellPosController::class, 'index']);
    $transaction_sub_type = '';
    $view_suspended_sell_url = action([\App\Http\Controllers\SellController::class, 'index']) . '?suspended=1';
    $pos_redirect_url = action([\App\Http\Controllers\SellPosController::class, 'create']);

    foreach ($pos_module_data ?? [] as $module_data) {
        $go_back_url = $module_data['go_back_url'] ?? $go_back_url;
        if (!empty($module_data['transaction_sub_type'])) {
            $transaction_sub_type = $module_data['transaction_sub_type'];
            $view_suspended_sell_url .= '&transaction_sub_type=' . $transaction_sub_type;
            $pos_redirect_url .= '?sub_type=' . $transaction_sub_type;
        }
    }
@endphp

<input type="hidden" name="transaction_sub_type" id="transaction_sub_type" value="{{ $transaction_sub_type }}">

<div class="clean-pos-toolbar no-print">
    <input type="hidden" id="pos_redirect_url" value="{{ $pos_redirect_url }}">

    <div class="clean-pos-location">
        <span class="clean-pos-store-icon"><i class="fas fa-store"></i></span>
        @if (empty($transaction->location_id))
            {!! Form::select('select_location_id', $business_locations, $default_location->id ?? null, [
                'class' => 'form-control input-sm clean-pos-location-select',
                'id' => 'select_location_id',
                'required',
                'aria-label' => __('sale.location'),
            ], $bl_attributes) !!}
        @else
            <strong class="clean-pos-location-name">{{ $transaction->location->name }}</strong>
        @endif
    </div>

    <div class="clean-pos-register-status">
        <span class="clean-pos-status-dot"></span>
        <span>Register open</span>
        <span class="curr_datetime clean-pos-time">{{ @format_datetime('now') }}</span>
    </div>

    <nav class="clean-pos-actions" aria-label="POS navigation">
        <button type="button" class="clean-pos-action hover-q" title="Keyboard shortcuts"
            aria-label="Keyboard shortcuts" data-container="body" data-toggle="popover"
            data-placement="bottom" data-content="@include('sale_pos.partials.keyboard_shortcuts_details')"
            data-html="true" data-trigger="hover focus">
            <i class="fas fa-keyboard"></i><span>Shortcuts</span>
        </button>

        <a href="{{ $go_back_url }}" class="clean-pos-action" title="{{ __('lang_v1.go_back') }}">
            <i class="fas fa-home"></i><span>Home</span>
        </a>
        @can('view_cash_register')
            <button type="button" id="register_details" class="clean-pos-action btn-modal"
                data-container=".register_details_modal"
                data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getRegisterDetails']) }}">
                <i class="fas fa-cash-register"></i><span>@lang('cash_register.register_details')</span>
            </button>
        @endcan
        @can('close_cash_register')
            <button type="button" id="close_register" class="clean-pos-action clean-pos-action-danger btn-modal"
                data-container=".close_register_modal"
                data-href="{{ action([\App\Http\Controllers\CashRegisterController::class, 'getCloseRegister']) }}">
                <i class="fas fa-lock"></i><span>@lang('cash_register.close_register')</span>
            </button>
        @endcan
        <button type="button" id="view_suspended_sales" class="clean-pos-action btn-modal"
            data-container=".view_modal" data-href="{{ $view_suspended_sell_url }}">
            <i class="fas fa-pause-circle"></i><span>@lang('lang_v1.view_suspended_sales')</span>
        </button>
        <div class="clean-pos-more">
            <button type="button" class="clean-pos-action" aria-expanded="false"
                onclick="const menu=this.nextElementSibling; const open=menu.classList.toggle('is-open'); this.setAttribute('aria-expanded', open ? 'true' : 'false');">
                <i class="fas fa-ellipsis-h"></i><span>More</span>
            </button>
            <div class="clean-pos-menu">
                <button id="btnCalculator" type="button" class="clean-pos-menu-item popover-default"
                    data-toggle="popover" data-trigger="click" data-content='@include('layouts.partials.calculator')'
                    data-html="true" data-placement="bottom">
                    <i class="fas fa-calculator"></i><span>@lang('lang_v1.calculator')</span>
                </button>
                <button type="button" id="return_sale" class="clean-pos-menu-item popover-default"
                    data-toggle="popover" data-trigger="click"
                    data-content='<div class="m-8"><input type="text" class="form-control" placeholder="@lang('sale.invoice_no')" id="send_for_sell_return_invoice_no"></div><div class="w-100 text-center"><button type="button" class="tw-dw-btn tw-dw-btn-error tw-text-white tw-dw-btn-sm" id="send_for_sell_return">@lang('lang_v1.send')</button></div>'
                    data-html="true" data-placement="bottom">
                    <i class="fas fa-undo"></i><span>@lang('lang_v1.sell_return')</span>
                </button>
                <button type="button" id="full_screen" class="clean-pos-menu-item">
                    <i class="fas fa-expand"></i><span>@lang('lang_v1.full_screen')</span>
                </button>
                @can('expense.add')
                    <button type="button" id="add_expense" class="clean-pos-menu-item btn-modal">
                        <i class="fas fa-minus-circle"></i><span>@lang('expense.add_expense')</span>
                    </button>
                @endcan
            </div>
        </div>
    </nav>
</div>

<div class="modal fade" id="service_staff_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
