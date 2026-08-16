@php
    $openedAt = \Carbon\Carbon::parse($register_details->open_time);
    $denominations = collect(\App\Utils\CashDenominationUtil::fromSettings($pos_settings));
    $notes = $denominations->where('type', 'note');
    $coins = $denominations->where('type', 'coin');
    $manualPayments = collect($payment_breakdown)->where('requires_confirmation', true);
@endphp
<div class="modal-dialog close-register-dialog" role="document">
<div class="modal-content close-register-workspace">
{!! Form::open(['url' => action([\App\Http\Controllers\CashRegisterController::class, 'postCloseRegister']), 'method' => 'post', 'id' => 'close_cash_register_form']) !!}
    {!! Form::hidden('register_id', $register_details->register_id) !!}
    <header class="close-register-header">
        <div class="close-register-heading"><span><i class="fas fa-lock"></i></span><div><small>Register lifecycle &middot; Closing checkpoint</small><h3>Close Register</h3><p>{{ $register_details->location_name }} &middot; {{ trim($register_details->user_name) }}</p></div></div>
        <div class="close-register-session"><strong>{{ $openedAt->diffForHumans(now(), true) }}</strong><span>Opened {{ $openedAt->format('d M Y, h:i A') }}</span></div>
        <button type="button" class="close-register-x" data-dismiss="modal" aria-label="Close">&times;</button>
    </header>

    <div class="close-register-body">
        <div class="close-register-kpis">
            <article><span>Opening cash</span><strong class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand }}</strong></article>
            <article><span>Gross sales</span><strong class="display_currency" data-currency_symbol="true">{{ $gross_sales }}</strong></article>
            <article class="is-negative"><span>Refunds</span><strong class="display_currency" data-currency_symbol="true">{{ $register_details->total_refund }}</strong></article>
            <article class="is-negative"><span>Expenses</span><strong class="display_currency" data-currency_symbol="true">{{ $register_details->total_expense }}</strong></article>
            <article><span>Credit sales</span><strong class="display_currency" data-currency_symbol="true">{{ $credit_sales }}</strong></article>
        </div>

        <div class="close-register-layout" data-expected-cash="{{ $expected_cash }}">
            <main class="close-register-entry">
                <section class="close-register-section">
                    <div class="close-register-section-title"><span><i class="fas fa-coins"></i></span><div><h4>Count the cash drawer</h4><p>Enter the number of notes and coins physically present.</p></div></div>
                    @if($denominations->isNotEmpty())
                        <div class="cash_denomination_div">
                            <div class="close-denomination-columns pos-denomination-grid">
                                @foreach(['Notes' => $notes, 'Coins' => $coins] as $group => $values)
                                    @if($values->isNotEmpty())
                                    <div class="close-denomination-group"><header><strong>{{ $group }}</strong><span>Count & subtotal</span></header>
                                        @foreach($values as $dnm)
                                            <div class="pos-denomination-row close-denomination-row"><b>{{ $dnm['label'] }}</b><span>&times;</span>{!! Form::number("denominations[{$dnm['code']}]", null, ['class' => 'form-control cash_denomination', 'min' => 0, 'max' => 1000000, 'step' => 1, 'required' => true, 'data-denomination' => $dnm['value'], 'data-denomination-code' => $dnm['code'], 'inputmode' => 'numeric', 'aria-label' => $group.' '.$dnm['label'], 'placeholder' => '0']) !!}<span>=</span><strong class="denomination_subtotal">0.00</strong></div>
                                        @endforeach
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                            <div class="close-denomination-total"><span>Counted from denominations</span><strong class="denomination_total">0.00</strong><input type="hidden" class="denomination_total_amount" value="0"></div>
                        </div>
                    @else
                        <div class="close-register-notice is-danger"><i class="fas fa-exclamation-triangle"></i><span>Cash denominations are not configured. Configure them in Business Settings before closing this register.</span></div>
                    @endif
                </section>

                <section class="close-register-section">
                    <div class="close-register-section-title"><span><i class="fas fa-clipboard-check"></i></span><div><h4>Closing records</h4><p>Confirm physical cash and supporting payment documents.</p></div></div>
                    <div class="close-register-fields">
                        <label><span>Counted closing cash *</span><div class="close-register-input"><i class="fas fa-money-bill-wave"></i>{!! Form::text('closing_amount', $denominations->isNotEmpty() ? @num_format(0) : @num_format($expected_cash), ['class' => 'form-control input_number', 'required', 'inputmode' => 'decimal', 'autocomplete' => 'off', 'readonly' => $denominations->isNotEmpty()]) !!}</div>@if($denominations->isNotEmpty())<small class="close-counted-authority"><i class="fas fa-calculator"></i> Calculated from the denomination count</small>@endif</label>
                        <label><span>Total card slips *</span><div class="close-register-input"><i class="fas fa-credit-card"></i>{!! Form::number('total_card_slips', $register_details->total_card_slips, ['class' => 'form-control', 'required', 'min' => 0]) !!}</div></label>
                        <label><span>Total cheques *</span><div class="close-register-input"><i class="fas fa-money-check"></i>{!! Form::number('total_cheques', $register_details->total_cheques, ['class' => 'form-control', 'required', 'min' => 0]) !!}</div></label>
                    </div>
                    <label class="close-register-note"><span>Closing note <small>Optional audit context</small></span>{!! Form::textarea('closing_note', null, ['class' => 'form-control', 'rows' => 3, 'maxlength' => 2000, 'placeholder' => 'Explain shortages, overages, handover details or unusual activity...']) !!}</label>
                </section>

                @if($manualPayments->isNotEmpty())
                <div class="close-momo-confirmation-alert"><span><i class="fas fa-clipboard-check"></i></span><div><strong>Manual payment confirmation required</strong><p>Check each active non-cash channel against its provider account, slips or supporting documents. Enter the actual received total for every method before closing.</p></div></div>
                @endif
                <details class="close-register-payments" open>
                    <summary><span><i class="fas fa-wallet"></i> Active payment modes <small>System-generated from finalized payments</small></span><i class="fas fa-chevron-down"></i></summary>
                    <div><table><thead><tr><th>Method</th><th>Sales</th><th>Refunds</th><th>Expenses</th><th>System net</th><th>Confirmed</th><th>Variance</th></tr></thead><tbody>
                        @foreach($payment_breakdown as $payment)<tr @if($payment['requires_confirmation']) class="close-payment-confirm-row" data-payment-expected="{{ $payment['net'] }}" @endif><td><span class="close-payment-active-dot"></span>{{ $payment['label'] }}@if($payment['requires_confirmation'])<small>Manual confirmation required</small>@else<small>Counted by denomination</small>@endif</td><td class="display_currency" data-currency_symbol="true">{{ $payment['sales'] }}</td><td class="is-negative display_currency" data-currency_symbol="true">{{ $payment['refunds'] }}</td><td class="is-negative display_currency" data-currency_symbol="true">{{ $payment['expenses'] }}</td><td class="is-net display_currency" data-currency_symbol="true">{{ $payment['net'] }}</td><td>@if($payment['requires_confirmation'])<input type="text" class="form-control input_number close-payment-confirm-input" name="payment_confirmations[{{ $payment['method'] }}]" required inputmode="decimal" autocomplete="off" placeholder="Required actual total">@else<span class="close-system-only">Denominations</span>@endif</td><td>@if($payment['requires_confirmation'])<strong class="close-payment-variance is-balanced" data-payment-variance>—</strong>@else<span>—</span>@endif</td></tr>@endforeach
                    </tbody></table></div>
                </details>
            </main>

            <aside class="close-register-summary">
                <header><small>Live reconciliation</small><h4>Cash position</h4></header>
                <div><span>Opening cash</span><strong class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand }}</strong></div>
                <div><span>Cash sales</span><strong class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash }}</strong></div>
                <div class="is-deduction"><span>Cash refunds</span><strong>- <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_refund }}</span></strong></div>
                <div class="is-deduction"><span>Cash expenses</span><strong>- <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_expense }}</span></strong></div>
                <div class="is-expected"><span>Expected cash</span><strong class="display_currency" data-currency_symbol="true">{{ $expected_cash }}</strong></div>
                <div><span>Declared cash</span><strong data-declared-cash>0.00</strong></div>
                <div><span>Denomination count</span><strong data-counted-cash>0.00</strong></div>
                <div class="close-register-variance is-balanced"><span>Variance</span><strong data-cash-variance>0.00</strong><small data-variance-label>Balanced</small></div>
                <p><i class="fas fa-shield-alt"></i> This close is recorded against register #{{ $register_details->register_id }} and cannot be submitted twice.</p>
            </aside>
        </div>
    </div>

    <footer class="close-register-footer"><span><i class="fas fa-info-circle"></i> Complete every denomination count and non-cash confirmation before closing.</span><div><button type="button" class="close-register-cancel" data-dismiss="modal">Cancel</button><button type="button" class="close-register-review" data-review-close @if($denominations->isEmpty()) disabled title="Configure cash denominations in Business Settings first" @endif><i class="fas fa-clipboard-check"></i> Review & Close</button></div></footer>

    <div class="close-register-confirm" aria-hidden="true">
        <div>
            <span class="close-confirm-icon"><i class="fas fa-lock"></i></span>
            <small>Final checkpoint</small>
            <h3>Review closing totals</h3>
            <p>Verify the cash drawer and every active payment channel before closing.</p>
            <div class="close-confirm-session"><span>Location</span><strong>{{ $register_details->location_name }}</strong></div>
            <div class="close-confirm-payment-summary">
                <header><span>Payment method</span><span>System</span><span>Confirmed</span><span>Variance</span></header>
                <div class="is-cash">
                    <strong><i class="fas fa-money-bill-wave"></i> Cash</strong>
                    <span class="display_currency" data-currency_symbol="true">{{ $expected_cash }}</span>
                    <span data-confirm-declared>0.00</span>
                    <span data-confirm-variance>0.00</span>
                </div>
                @foreach($manualPayments as $payment)
                    <div data-confirm-payment="{{ $payment['method'] }}" data-system-total="{{ $payment['net'] }}">
                        <strong><i class="fas fa-wallet"></i> {{ $payment['label'] }}</strong>
                        <span class="display_currency" data-currency_symbol="true">{{ $payment['net'] }}</span>
                        <span data-confirm-payment-actual>0.00</span>
                        <span data-confirm-payment-variance>0.00</span>
                    </div>
                @endforeach
                <footer>
                    <strong>Combined total</strong>
                    <span data-confirm-system-grand>0.00</span>
                    <span data-confirm-actual-grand>0.00</span>
                    <span data-confirm-variance-grand>0.00</span>
                </footer>
            </div>
            <label><input type="checkbox" data-confirm-check><span>I confirm the denomination count and all payment totals are correct.</span></label>
            <div class="close-confirm-actions"><button type="button" data-back-close>Go Back</button><button type="submit" data-submit-close disabled><i class="fas fa-lock"></i> Close Register</button></div>
        </div>
    </div>
{!! Form::close() !!}
</div>
</div>

<script>
(function () {
    var modal = $('.close_register_modal');
    var form = modal.find('#close_cash_register_form');
    var layout = form.find('.close-register-layout');
    var expected = parseFloat(layout.data('expected-cash')) || 0;
    function numberFrom(input) { var value = typeof __number_uf === 'function' ? __number_uf(input.val()) : parseFloat(String(input.val()).replace(/,/g,'')); return isFinite(value) ? value : 0; }
    function money(value) { return __currency_trans_from_en(value, true); }
    function inputNumber(value) {
        var precision = typeof __currency_precision !== 'undefined' ? __currency_precision : 2;
        return typeof __number_f === 'function' ? __number_f(value, false, false, precision) : (parseFloat(value) || 0).toFixed(precision);
    }
    function refreshReconciliation() {
        var declared = numberFrom(form.find('[name="closing_amount"]'));
        var counted = 0;
        form.find('.cash_denomination').each(function () {
            var subtotal = (parseFloat($(this).data('denomination')) || 0) * (parseInt($(this).val(), 10) || 0);
            counted += subtotal;
            $(this).closest('.close-denomination-row').find('.denomination_subtotal').text(money(subtotal));
        });
        form.find('.denomination_total_amount').val(counted);
        form.find('.denomination_total').text(money(counted));
        if (form.find('.cash_denomination').length) {
            form.find('[name="closing_amount"]').val(inputNumber(counted));
            declared = counted;
        }
        var variance = declared - expected;
        form.find('[data-declared-cash]').text(money(declared)); form.find('[data-counted-cash]').text(money(counted)); form.find('[data-cash-variance]').text(money(Math.abs(variance)));
        var box = form.find('.close-register-variance').removeClass('is-short is-over is-balanced');
        var label = variance < -.005 ? 'Short' : (variance > .005 ? 'Over' : 'Balanced'); box.addClass(variance < -.005 ? 'is-short' : (variance > .005 ? 'is-over' : 'is-balanced')).find('[data-variance-label]').text(label);
        form.find('[data-confirm-declared]').text(money(declared)); form.find('[data-confirm-variance]').text((variance < 0 ? '- ' : variance > 0 ? '+ ' : '') + money(Math.abs(variance)));
        refreshConfirmationSummary(declared);
    }
    function refreshConfirmationSummary(declared) {
        var systemGrand = expected;
        var actualGrand = declared;
        form.find('[data-confirm-payment]').each(function () {
            var summaryRow = $(this);
            var method = String(summaryRow.data('confirm-payment'));
            var systemTotal = parseFloat(summaryRow.data('system-total')) || 0;
            var source = form.find('.close-payment-confirm-input').filter(function () {
                return this.name === 'payment_confirmations[' + method + ']';
            }).first();
            var actual = numberFrom(source);
            var variance = actual - systemTotal;
            systemGrand += systemTotal;
            actualGrand += actual;
            summaryRow.find('[data-confirm-payment-actual]').text(money(actual));
            summaryRow.find('[data-confirm-payment-variance]').text((variance < 0 ? '- ' : variance > 0 ? '+ ' : '') + money(Math.abs(variance)));
        });
        var grandVariance = actualGrand - systemGrand;
        form.find('[data-confirm-system-grand]').text(money(systemGrand));
        form.find('[data-confirm-actual-grand]').text(money(actualGrand));
        form.find('[data-confirm-variance-grand]').text((grandVariance < 0 ? '- ' : grandVariance > 0 ? '+ ' : '') + money(Math.abs(grandVariance)));
    }
    modal.off('.closeRegisterLifecycle');
    modal.on('input.closeRegisterLifecycle change.closeRegisterLifecycle', '.cash_denomination, [name="closing_amount"]', function () { window.setTimeout(refreshReconciliation, 0); });
    modal.on('click.closeRegisterLifecycle', '[data-review-close]', function () { if (form[0].checkValidity && !form[0].checkValidity()) { form[0].reportValidity(); return; } refreshReconciliation(); form.find('[data-confirm-check]').prop('checked', false); form.find('[data-submit-close]').prop('disabled', true); form.find('.close-register-confirm').addClass('is-visible').attr('aria-hidden','false'); });
    modal.on('click.closeRegisterLifecycle', '[data-back-close]', function () { form.find('.close-register-confirm').removeClass('is-visible').attr('aria-hidden','true'); });
    modal.on('change.closeRegisterLifecycle', '[data-confirm-check]', function () { form.find('[data-submit-close]').prop('disabled', !this.checked); });
    modal.on('input.closeRegisterLifecycle change.closeRegisterLifecycle', '.close-payment-confirm-input', function () {
        var row = $(this).closest('[data-payment-expected]');
        var actual = numberFrom($(this));
        var expectedPayment = parseFloat(row.data('payment-expected')) || 0;
        var variance = actual - expectedPayment;
        var output = row.find('[data-payment-variance]').removeClass('is-short is-over is-balanced');
        output.addClass(variance < -.005 ? 'is-short' : (variance > .005 ? 'is-over' : 'is-balanced')).text((variance < 0 ? '- ' : variance > 0 ? '+ ' : '') + money(Math.abs(variance)));
        refreshReconciliation();
    });
    form.off('submit.closeRegisterLifecycle').on('submit.closeRegisterLifecycle', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        var button = form.find('[data-submit-close]');
        if (form.data('submitting')) { return false; }

        form.data('submitting', true);
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Closing...');

        $.ajax({
            method: 'POST',
            url: form.attr('action'),
            data: form.serialize(),
            dataType: 'json',
            headers: {'Accept': 'application/json'},
            success: function (result) {
                if (result && result.success) {
                    if (typeof toastr !== 'undefined') { toastr.success(result.msg); }
                    modal.modal('hide');
                    window.setTimeout(function () { window.location.reload(); }, 250);
                    return;
                }
                form.data('submitting', false);
                button.prop('disabled', false).html('<i class="fas fa-lock"></i> Close Register');
                if (typeof toastr !== 'undefined') { toastr.error((result && result.msg) || 'The register could not be closed.'); }
            },
            error: function (xhr) {
                form.data('submitting', false);
                button.prop('disabled', false).html('<i class="fas fa-lock"></i> Close Register');
                var response = xhr.responseJSON || {};
                var message = response.message || 'The register could not be closed. Please review the entered totals.';
                if (response.errors) {
                    var keys = Object.keys(response.errors);
                    if (keys.length && response.errors[keys[0]].length) { message = response.errors[keys[0]][0]; }
                }
                if (typeof toastr !== 'undefined') { toastr.error(message); } else { alert(message); }
            }
        });

        return false;
    });
    __currency_convert_recursively(modal); refreshReconciliation();
})();
</script>
