@extends('layouts.app')
@section('title', __('cash_register.open_cash_register'))

@section('css')
    <link rel="stylesheet" href="{{ asset('css/pos-redesign.css?v=' . filemtime(public_path('css/pos-redesign.css'))) }}">
@endsection

@section('content')
<section class="register-open-page">
    <div class="register-open-shell">
        <div class="register-open-intro">
            <span class="register-open-eyebrow"><i class="fas fa-shield-alt"></i> Register control</span>
            <h1>Start a new register session</h1>
            <p>Confirm the counter and record the physical cash available before the first sale. This amount becomes the reconciliation baseline at closing.</p>
            <div class="register-open-principles">
                <article><i class="fas fa-map-marker-alt"></i><div><strong>Correct location</strong><span>Sales and stock movements stay tied to this counter.</span></div></article>
                <article><i class="fas fa-wallet"></i><div><strong>Accurate opening cash</strong><span>Count the drawer before entering the starting float.</span></div></article>
                <article><i class="fas fa-clipboard-check"></i><div><strong>Complete audit trail</strong><span>Opening, activity and closing remain linked to your account.</span></div></article>
            </div>
        </div>

        <div class="register-open-card">
            <header>
                <span class="register-open-icon"><i class="fas fa-cash-register"></i></span>
                <div><small>Opening checkpoint</small><h2>Open register</h2><p>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p></div>
            </header>

            @if($business_locations->count() > 0)
                {!! Form::open(['url' => action([\App\Http\Controllers\CashRegisterController::class, 'store']), 'method' => 'post', 'id' => 'add_cash_register_form', 'class' => 'register-open-form']) !!}
                    <input type="hidden" name="sub_type" value="{{ $sub_type }}">
                    <div class="register-open-field">
                        <label for="location_id"><span>Business location</span><small>Where this register will operate</small></label>
                        @if(count($business_locations) > 1)
                            <div class="register-open-input"><i class="fas fa-store"></i>{!! Form::select('location_id', $business_locations, old('location_id'), ['id' => 'location_id', 'class' => 'form-control select2', 'required', 'placeholder' => __('lang_v1.select_location')]) !!}</div>
                        @else
                            @php $singleLocationId = array_key_first($business_locations->toArray()); @endphp
                            {!! Form::hidden('location_id', $singleLocationId, ['id' => 'location_id']) !!}
                            <div class="register-open-location"><i class="fas fa-store"></i><strong>{{ $business_locations->first() }}</strong><span>Assigned location</span></div>
                        @endif
                    </div>
                    <div class="register-open-field">
                        <label><span>Active payment modes</span><small>Generated from the selected location</small></label>
                        <div class="register-opening-payment-modes" data-opening-payment-modes></div>
                    </div>
                    <label class="register-opening-cash-toggle"><input type="checkbox" name="has_opening_cash" value="1" checked><span><strong>Opening cash is present</strong><small>Turn this off when the drawer starts at zero.</small></span></label>
                    @if(!empty($cash_denominations))
                        <div class="register-open-field register-opening-count register-opening-cash-dependent">
                            <label><span>Opening cash count</span><small>Enter counts you have; leave unused denominations blank (0)</small></label>
                            <div class="register-opening-denominations">
                                @foreach(['note' => 'Notes', 'coin' => 'Coins'] as $type => $groupLabel)
                                    @php $groupItems = collect($cash_denominations)->where('type', $type); @endphp
                                    @if($groupItems->isNotEmpty())
                                    <section><header><strong>{{ $groupLabel }}</strong><small>Count & subtotal</small></header>
                                        @foreach($groupItems as $item)
                                            <div class="register-opening-denomination-row"><b>{{ $item['label'] }}</b><span>&times;</span><input type="number" class="form-control opening-denomination-input" name="opening_denominations[{{ $item['code'] }}]" value="" placeholder="0" min="0" max="1000000" step="1" inputmode="numeric" data-value="{{ $item['value'] }}"><span>=</span><strong data-opening-subtotal>0.00</strong></div>
                                        @endforeach
                                    </section>
                                    @endif
                                @endforeach
                            </div>
                            <div class="register-opening-count-total"><span>Counted opening cash</span><strong data-opening-count-total>0.00</strong></div>
                        </div>
                    @endif
                    <div class="register-open-field register-opening-cash-dependent">
                        <label for="amount"><span>{{ __('cash_register.cash_in_hand') }}</span><small>Physical starting float in the drawer</small></label>
                        <div class="register-open-input register-open-amount"><b>{{ session('currency.symbol') }}</b>{!! Form::text('amount', old('amount', '0'), ['id' => 'amount', 'class' => 'form-control input_number', 'inputmode' => 'decimal', 'autocomplete' => 'off', 'required', 'readonly' => !empty($cash_denominations), 'placeholder' => __('cash_register.enter_amount')]) !!}</div>
                        @if(!empty($cash_denominations))<small class="register-opening-auto-note"><i class="fas fa-calculator"></i> Automatically calculated from the denomination count.</small>@endif
                    </div>
                    <div class="register-open-baseline register-opening-cash-dependent"><span>Opening cash baseline</span><strong class="register-open-preview">{{ session('currency.symbol') }} 0.00</strong><small>Expected closing cash starts here, then adjusts for cash sales, refunds and expenses.</small></div>
                    <label class="register-open-confirm"><input type="checkbox" required><span>I have counted the drawer and confirmed the location and opening cash.</span></label>
                    <button type="submit" class="register-open-submit"><i class="fas fa-lock-open"></i><span>Open Register</span><small>Start selling</small></button>
                {!! Form::close() !!}
            @else
                <div class="register-open-empty"><i class="fas fa-map-marker-alt"></i><h3>@lang('lang_v1.no_location_access_found')</h3><p>Ask an administrator to assign at least one business location to your account.</p></div>
            @endif
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
(function () {
    var form = $('#add_cash_register_form');
    var amount = form.find('#amount');
    var paymentModesByLocation = @json($payment_types_by_location);
    var paymentIcons = {cash:'fa-money-bill-wave',card:'fa-credit-card',cheque:'fa-money-check',bank_transfer:'fa-university',other:'fa-ellipsis-h'};
    function formatInputNumber(value) {
        var precision = typeof __currency_precision !== 'undefined' ? __currency_precision : 2;
        if (typeof __number_f === 'function') {
            return __number_f(value, false, false, precision);
        }
        return (parseFloat(value) || 0).toFixed(precision);
    }
    function updateOpeningPreview() {
        var value = typeof __number_uf === 'function' ? __number_uf(amount.val()) : parseFloat(String(amount.val()).replace(/,/g, ''));
        value = isFinite(value) && value >= 0 ? value : 0;
        form.find('.register-open-preview').text(__currency_trans_from_en(value, true));
    }
    function updateOpeningCount() {
        var total = 0;
        form.find('.opening-denomination-input').each(function () {
            var subtotal = (parseFloat($(this).data('value')) || 0) * (parseInt($(this).val(), 10) || 0);
            total += subtotal;
            $(this).closest('.register-opening-denomination-row').find('[data-opening-subtotal]').text(__currency_trans_from_en(subtotal, true));
        });
        form.find('[data-opening-count-total]').text(__currency_trans_from_en(total, true));
        if (form.find('.opening-denomination-input').length) { amount.val(formatInputNumber(total)); }
        updateOpeningPreview();
    }
    function renderPaymentModes() {
        var locationId = form.find('[name="location_id"]').val();
        var methods = paymentModesByLocation[locationId] || {};
        var target = form.find('[data-opening-payment-modes]').empty();
        Object.keys(methods).forEach(function (key) {
            $('<span>').addClass('register-opening-payment-mode').append($('<i>').addClass('fas ' + (paymentIcons[key] || 'fa-wallet'))).append($('<b>').text(methods[key])).appendTo(target);
        });
        if (!Object.keys(methods).length) { target.append('<em>No payment method is active for this location.</em>'); }
        var cashActive = Object.prototype.hasOwnProperty.call(methods, 'cash');
        var toggle = form.find('[name="has_opening_cash"]');
        toggle.prop('disabled', !cashActive);
        if (!cashActive) { toggle.prop('checked', false); }
        toggleOpeningCash();
    }
    function toggleOpeningCash() {
        var enabled = form.find('[name="has_opening_cash"]').is(':checked:not(:disabled)');
        form.find('.register-opening-cash-dependent').toggle(enabled);
        if (!enabled) { form.find('.opening-denomination-input').val(''); amount.val(formatInputNumber(0)); }
        updateOpeningCount();
    }
    amount.on('input change', updateOpeningPreview);
    form.on('input change', '.opening-denomination-input', updateOpeningCount);
    form.on('change', '[name="location_id"]', renderPaymentModes);
    form.on('change', '[name="has_opening_cash"]', toggleOpeningCash);
    form.on('submit', function () {
        var button = form.find('.register-open-submit');
        if (button.prop('disabled')) { return false; }
        button.prop('disabled', true).addClass('is-loading').find('span').text('Opening register...');
    });
    renderPaymentModes();
})();
</script>
@endsection
