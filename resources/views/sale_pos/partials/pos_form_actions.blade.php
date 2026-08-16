@php
    $is_mobile = isMobile();
@endphp

<div class="pos-form-actions pos-v2-action-bar">
    <div class="pos-v2-action-inner">
        <div class="pos-v2-action-tools" aria-label="Sale options">
            @if (!Gate::check('disable_draft') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                <button type="button" class="pos-v2-action-button pos-v2-action-button--quiet @if ($pos_settings['disable_draft'] != 0) hide @endif"
                    id="pos-draft" @if (!empty($only_payment)) disabled @endif>
                    <i class="fas fa-file-alt" aria-hidden="true"></i><span>@lang('sale.draft')</span>
                </button>
            @endif

            @if (!Gate::check('disable_quotation') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                <button type="button" class="pos-v2-action-button pos-v2-action-button--quiet"
                    id="pos-quotation" @if (!empty($only_payment)) disabled @endif>
                    <i class="fas fa-file-invoice" aria-hidden="true"></i><span>@lang('lang_v1.quotation')</span>
                </button>
            @endif

            @if ((!Gate::check('disable_suspend_sale') || auth()->user()->can('superadmin') || auth()->user()->can('admin')) && empty($pos_settings['disable_suspend']))
                <button type="button" class="pos-v2-action-button pos-v2-action-button--quiet no-print pos-express-finalize"
                    data-pay_method="suspend" title="@lang('lang_v1.tooltip_suspend')"
                    @if (!empty($only_payment)) disabled @endif>
                    <i class="fas fa-pause-circle" aria-hidden="true"></i><span>@lang('lang_v1.suspend')</span>
                </button>
            @endif

            @if ((!Gate::check('disable_credit_sale') || auth()->user()->can('superadmin') || auth()->user()->can('admin')) && empty($pos_settings['disable_credit_sale_button']))
                <input type="hidden" name="is_credit_sale" value="0" id="is_credit_sale">
                <button type="button" class="pos-v2-action-button pos-v2-action-button--quiet no-print pos-express-finalize"
                    data-pay_method="credit_sale" title="@lang('lang_v1.tooltip_credit_sale')"
                    @if (!empty($only_payment)) disabled @endif>
                    <i class="fas fa-user-clock" aria-hidden="true"></i><span>@lang('lang_v1.credit_sale')</span>
                </button>
            @endif

            @if (!Gate::check('disable_card') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                <button type="button"
                    class="pos-v2-action-button pos-v2-action-button--quiet no-print pos-express-finalize @if (!array_key_exists('card', $payment_types)) hide @endif"
                    data-pay_method="card" title="@lang('lang_v1.tooltip_express_checkout_card')">
                    <i class="fas fa-credit-card" aria-hidden="true"></i><span>@lang('lang_v1.express_checkout_card')</span>
                </button>
            @endif

            @if (!isset($pos_settings['hide_recent_trans']) || $pos_settings['hide_recent_trans'] == 0)
                <button type="button" class="pos-v2-action-button pos-v2-action-button--quiet"
                    data-toggle="modal" data-target="#recent_transactions_modal" id="recent-transactions">
                    <i class="fas fa-clock" aria-hidden="true"></i><span>@lang('lang_v1.recent_transactions')</span>
                </button>
            @endif
        </div>

        <div class="pos-v2-payment-cluster">
        <div class="pos-v2-action-summary">
            <span class="pos-v2-action-summary-label">@lang('sale.total_payable')</span>
            <input type="hidden" name="final_total" id="final_total_input" value="0.00">
            <span id="total_payable" class="number pos-v2-action-total" aria-live="polite">0.00</span>
        </div>

        <div class="pos-v2-action-primary" aria-label="Payment options">
            @if (!Gate::check('disable_express_checkout') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                <button type="button"
                    class="pos-v2-action-button pos-v2-action-button--cash no-print pos-express-finalize @if ($pos_settings['disable_express_checkout'] != 0 || !array_key_exists('cash', $payment_types)) hide @endif"
                    data-pay_method="cash" title="@lang('tooltip.express_checkout')">
                    <i class="fas fa-money-bill-wave" aria-hidden="true"></i><span>@lang('lang_v1.express_checkout_cash')</span>
                </button>
            @endif

            @if (!Gate::check('disable_pay_checkout') || auth()->user()->can('superadmin') || auth()->user()->can('admin'))
                <button type="button"
                    class="pos-v2-action-button pos-v2-action-button--checkout no-print @if ($pos_settings['disable_pay_checkout'] != 0) hide @endif"
                    id="pos-finalize" title="@lang('lang_v1.tooltip_checkout_multi_pay')">
                    <i class="fas fa-arrow-right" aria-hidden="true"></i><span>@lang('lang_v1.checkout_multi_pay')</span>
                </button>
            @endif

            @if (empty($edit))
                <button type="button" class="pos-v2-action-button pos-v2-action-button--danger" id="pos-cancel">
                    <i class="fas fa-times" aria-hidden="true"></i><span>@lang('sale.cancel')</span>
                </button>
            @else
                <button type="button" class="pos-v2-action-button pos-v2-action-button--danger hide" id="pos-delete"
                    @if (!empty($only_payment)) disabled @endif>
                    <i class="fas fa-trash-alt" aria-hidden="true"></i><span>@lang('messages.delete')</span>
                </button>
            @endif

            <span class="curr_datetime pos-v2-action-clock" id="vintageClock" aria-label="Current time"></span>
        </div>
        </div>
    </div>
</div>

@if (isset($transaction))
    @include('sale_pos.partials.edit_discount_modal', [
        'sales_discount' => $transaction->discount_amount,
        'discount_type' => $transaction->discount_type,
        'rp_redeemed' => $transaction->rp_redeemed,
        'rp_redeemed_amount' => $transaction->rp_redeemed_amount,
        'max_available' => !empty($redeem_details['points']) ? $redeem_details['points'] : 0,
    ])
@else
    @include('sale_pos.partials.edit_discount_modal', [
        'sales_discount' => $business_details->default_sales_discount,
        'discount_type' => 'percentage',
        'rp_redeemed' => 0,
        'rp_redeemed_amount' => 0,
        'max_available' => 0,
    ])
@endif

@if (isset($transaction))
    @include('sale_pos.partials.edit_order_tax_modal', ['selected_tax' => $transaction->tax_id])
@else
    @include('sale_pos.partials.edit_order_tax_modal', [
        'selected_tax' => $business_details->default_sales_tax,
    ])
@endif

@include('sale_pos.partials.edit_shipping_modal')

<script>
    (function () {
        function updatePosClock() {
            var clock = document.getElementById('vintageClock');
            if (!clock) {
                return;
            }

            clock.textContent = new Date().toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        updatePosClock();
        window.setInterval(updatePosClock, 1000);
    })();
</script>
