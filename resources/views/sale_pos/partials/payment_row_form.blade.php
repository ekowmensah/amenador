<div class="row">
	<input type="hidden" class="payment_row_index" value="{{ $row_index}}">
	@php
		$col_class = 'col-md-6';
		if(!empty($accounts)){
			$col_class = 'col-md-4';
		}
		$readonly = $payment_line['method'] == 'advance' ? true : false;
	@endphp
	<div class="{{$col_class}}">
		<div class="form-group">
			{!! Form::label("amount_$row_index" ,__('sale.amount') . ':*') !!}
			<div class="input-group">
				<span class="input-group-addon">
					<i class="fas fa-money-bill-alt"></i>
				</span>
				{!! Form::text("payment[$row_index][amount]", @num_format($payment_line['amount']), ['class' => 'form-control payment-amount input_number', 'required', 'id' => "amount_$row_index", 'placeholder' => __('sale.amount'), 'readonly' => $readonly]); !!}
			</div>
		</div>
	</div>
	@if(!empty($show_date))
	<div class="{{$col_class}}">
		<div class="form-group">
			{!! Form::label("paid_on_$row_index" , __('lang_v1.paid_on') . ':*') !!}
			<div class="input-group">
              <span class="input-group-addon">
                <i class="fa fa-calendar"></i>
              </span>
              {!! Form::text("payment[$row_index][paid_on]", isset($payment_line['paid_on']) ? @format_datetime($payment_line['paid_on']) : @format_datetime('now'), ['class' => 'form-control paid_on', 'readonly', 'required']); !!}
            </div>
		</div>
	</div>
	@endif
	<div class="{{$col_class}}">
		<div class="form-group">
			{!! Form::label("method_$row_index" , __('lang_v1.payment_method') . ':*') !!}
			<div class="input-group">
				<span class="input-group-addon">
					<i class="fas fa-money-bill-alt"></i>
				</span>
				@php
					$_payment_method = empty($payment_line['method']) && array_key_exists('cash', $payment_types) ? 'cash' : $payment_line['method'];
				@endphp
				{!! Form::select("payment[$row_index][method]", $payment_types, $_payment_method, ['class' => 'form-control col-md-12 payment_types_dropdown', 'required', 'id' => !$readonly ? "method_$row_index" : "method_advance_$row_index", 'style' => 'width:100%;', 'disabled' => $readonly]); !!}

				@if($readonly)
					{!! Form::hidden("payment[$row_index][method]", $payment_line['method'], ['class' => 'payment_types_dropdown', 'required', 'id' => "method_$row_index"]); !!}
				@endif
			</div>
		</div>
	</div>

	@php
            $pos_settings = !empty(session()->get('business.pos_settings')) ? json_decode(session()->get('business.pos_settings'), true) : [];
            $enable_cash_denomination_for_payment_methods = !empty($pos_settings['enable_cash_denomination_for_payment_methods']) ? $pos_settings['enable_cash_denomination_for_payment_methods'] : [];
        @endphp

        @if(!empty($pos_settings['enable_cash_denomination_on']) && ($pos_settings['enable_cash_denomination_on'] == 'all_screens' || !empty($show_in_pos)) && !empty($show_denomination))
            <input type="hidden" class="enable_cash_denomination_for_payment_methods" value="{{json_encode($enable_cash_denomination_for_payment_methods)}}">
            <div class="clearfix"></div>
            <div class="col-md-12 cash_denomination_div @if(!in_array($payment_line['method'], $enable_cash_denomination_for_payment_methods)) hide @endif">
                <div class="pos-denomination-heading">
                    <span><i class="fas fa-money-bill-wave"></i></span>
                    <div><strong>@lang( 'lang_v1.cash_denominations' )</strong><small>Enter the quantity received for each value</small></div>
                </div>
                  @if(!empty($pos_settings['cash_denominations']))
                    @php
                        $all_denominations = \App\Utils\CashDenominationUtil::fromSettings($pos_settings);
                        $note_denominations = array_values(array_filter($all_denominations, fn($item) => $item['type'] === 'note'));
                        $coin_denominations = array_values(array_filter($all_denominations, fn($item) => $item['type'] === 'coin'));
                        $denomination_counts = [];
                        $total = 0;
                        foreach($payment_line['denominations'] ?? [] as $d) {
                            $saved_code = $d['denomination_code'] ?? null;
                            if ($saved_code) $denomination_counts[$saved_code] = $d['total_count'];
                            $total += $d['total_count'] * $d['amount'];
                        }
                    @endphp
                    <div class="pos-denomination-grid">
                        @foreach(['Notes' => $note_denominations, 'Coins' => $coin_denominations] as $group_name => $group_denominations)
                            <section class="pos-denomination-group">
                                <header><span>{{ $group_name }}</span><small>@lang('lang_v1.count') &amp; @lang('sale.subtotal')</small></header>
                                <div class="pos-denomination-list">
                                    @forelse($group_denominations as $dnm)
                                        @php
                                            $count = $denomination_counts[$dnm['code']] ?? 0;
                                            $sub_total = $count * $dnm['value'];
                                        @endphp
                                        <div class="pos-denomination-row">
                                            <strong>{{ $dnm['label'] }}</strong>
                                            <span class="pos-denomination-times">×</span>
                                            {!! Form::number("payment[$row_index][denominations][{$dnm['code']}]", $count, ['class' => 'form-control cash_denomination input-sm', 'min' => 0, 'data-denomination' => $dnm['value'], 'data-denomination-code' => $dnm['code'], 'inputmode' => 'numeric', 'aria-label' => $group_name . ' ' . $dnm['label']]); !!}
                                            <span class="pos-denomination-equals">=</span>
                                            <span class="denomination_subtotal">{{@num_format($sub_total)}}</span>
                                        </div>
                                    @empty
                                        <p class="pos-denomination-empty">No {{ strtolower($group_name) }} configured</p>
                                    @endforelse
                                </div>
                            </section>
                        @endforeach
                    </div>
                    <div class="pos-denomination-total">
                        <span>@lang('sale.total')</span><strong class="denomination_total">{{@num_format($total)}}</strong>
                        <input type="hidden" class="denomination_total_amount" value="{{$total}}">
                        <input type="hidden" class="is_strict" value="{{$pos_settings['cash_denomination_strict_check'] ?? ''}}">
                    </div>
                    <p class="cash_denomination_error error hide">@lang('lang_v1.cash_denomination_error')</p>
                  @else
                    <p class="help-block">@lang('lang_v1.denomination_add_help_text')</p>
                  @endif
            </div>
            <div class="clearfix"></div>
        @endif
	@if(!empty($accounts))
		<div class="{{$col_class}}">
			<div class="form-group @if($readonly) hide @endif">
				{!! Form::label("account_$row_index" , __('lang_v1.payment_account') . ':') !!}
				<div class="input-group">
					<span class="input-group-addon">
						<i class="fas fa-money-bill-alt"></i>
					</span>
					{!! Form::select("payment[$row_index][account_id]", $accounts, !empty($payment_line['account_id']) ? $payment_line['account_id'] : '' , ['class' => 'form-control select2 account-dropdown', 'id' => !$readonly ? "account_$row_index" : "account_advance_$row_index", 'style' => 'width:100%;', 'disabled' => $readonly]); !!}
				</div>
			</div>
		</div>
	@endif
	<div class="clearfix"></div>
		@include('sale_pos.partials.payment_type_details')
	<div class="col-md-12">
		<div class="form-group">
			{!! Form::label("note_$row_index", __('sale.payment_note') . ':') !!}
			{!! Form::textarea("payment[$row_index][note]", $payment_line['note'], ['class' => 'form-control', 'rows' => 3, 'id' => "note_$row_index"]); !!}
		</div>
	</div>
</div>
