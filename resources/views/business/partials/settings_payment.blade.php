<!--payment related settings -->
<div class="pos-tab-content">
    <div class="row">
        @php $configuredDenominations = \App\Utils\CashDenominationUtil::fromSettings($pos_settings); @endphp
        <div class="col-md-12">
            <div class="form-group denomination-settings-editor">
                <div class="denomination-settings-heading"><div><label>@lang('lang_v1.cash_denominations')</label><p>Define each physical note and coin once. Labels may share the same value, such as a ₵1 note and ₵1 coin.</p></div><div><button type="button" class="btn btn-default btn-sm" id="load_ghana_denominations"><i class="fas fa-magic"></i> Ghana preset</button><button type="button" class="btn btn-primary btn-sm" id="add_denomination_row"><i class="fas fa-plus"></i> Add denomination</button></div></div>
                <div class="denomination-settings-table"><div class="denomination-settings-labels"><span>Label</span><span>Value</span><span>Type</span><span></span></div><div id="denomination_settings_rows">
                    @foreach($configuredDenominations as $index => $item)
                        <div class="denomination-settings-row">
                            <input type="hidden" name="pos_settings[cash_denominations_v2][{{ $index }}][code]" value="{{ $item['code'] }}" data-field="code">
                            <input type="text" class="form-control" name="pos_settings[cash_denominations_v2][{{ $index }}][label]" value="{{ $item['label'] }}" data-field="label" required maxlength="80">
                            <input type="number" class="form-control" name="pos_settings[cash_denominations_v2][{{ $index }}][value]" value="{{ $item['value'] }}" data-field="value" min="0.0001" step="0.0001" required>
                            <select class="form-control" name="pos_settings[cash_denominations_v2][{{ $index }}][type]" data-field="type"><option value="note" @selected($item['type'] === 'note')>Note</option><option value="coin" @selected($item['type'] === 'coin')>Coin</option></select>
                            <button type="button" class="btn btn-link text-red remove-denomination-row" aria-label="Remove"><i class="fas fa-trash"></i></button>
                        </div>
                    @endforeach
                </div></div>
                <input type="hidden" name="pos_settings[cash_denominations]" value="{{ $pos_settings['cash_denominations'] ?? '' }}">
                <p class="help-block">This single configuration is used by Finalize Payment, strict cash checks, saved payment counts, Register Details and Close Register.</p>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('enable_cash_denomination_on', __('lang_v1.enable_cash_denomination_on') . ':') !!}
                {!! Form::select('pos_settings[enable_cash_denomination_on]', ['pos_screen' => __('lang_v1.pos_screen'), 'all_screens' => __('lang_v1.all_screen')], isset($pos_settings['enable_cash_denomination_on']) ? $pos_settings['enable_cash_denomination_on'] : 'pos_screen', ['class' => 'form-control', 'style' => 'width: 100%;' ]); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('enable_cash_denomination_for_payment_methods', __('lang_v1.enable_cash_denomination_for_payment_methods') . ':') !!}
                {!! Form::select('pos_settings[enable_cash_denomination_for_payment_methods][]', $payment_types, isset($pos_settings['enable_cash_denomination_for_payment_methods']) ? $pos_settings['enable_cash_denomination_for_payment_methods'] : null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'multiple' ]); !!}
            </div>
        </div>

        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                <br>
                  <label>
                    {!! Form::checkbox('pos_settings[cash_denomination_strict_check]', 1,  
                        !empty($pos_settings['cash_denomination_strict_check']) , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.strict_check' ) }}
                  </label>
                  @show_tooltip(__('lang_v1.strict_check_help'))
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.denomination-settings-editor{padding:16px;border:1px solid #dce7df;border-radius:12px;background:#f8fbf9}.denomination-settings-heading{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-bottom:12px}.denomination-settings-heading label{margin:0;color:#294437;font-size:13px;font-weight:800}.denomination-settings-heading p{margin:3px 0 0;color:#7a8981;font-size:10px}.denomination-settings-heading>div:last-child{display:flex;gap:6px}.denomination-settings-table{overflow:hidden;border:1px solid #d8e4dc;border-radius:10px;background:#fff}.denomination-settings-labels,.denomination-settings-row{display:grid;align-items:center;gap:8px;grid-template-columns:1.5fr 1fr 1fr 38px}.denomination-settings-labels{padding:8px 10px;background:#edf5f0;color:#718078;font-size:8px;font-weight:900;text-transform:uppercase}.denomination-settings-row{padding:7px 10px;border-top:1px solid #edf2ee}.denomination-settings-row .form-control{height:35px;border-color:#d5e1d9;border-radius:7px}.remove-denomination-row{padding:6px}.denomination-settings-empty{padding:25px;color:#849188;text-align:center;font-size:10px}@media(max-width:600px){.denomination-settings-heading{align-items:stretch;flex-direction:column}.denomination-settings-labels{display:none}.denomination-settings-row{grid-template-columns:1fr 1fr 35px}.denomination-settings-row input[data-field=label]{grid-column:1/-1}.denomination-settings-row select{grid-column:2}.denomination-settings-row .remove-denomination-row{grid-column:3;grid-row:2}.denomination-settings-heading>div:last-child button{flex:1}}
</style>
<script>
(function(){
    var rows=document.getElementById('denomination_settings_rows');
    var preset=[['GHS_200_NOTE','₵200 Note',200,'note'],['GHS_100_NOTE','₵100 Note',100,'note'],['GHS_50_NOTE','₵50 Note',50,'note'],['GHS_20_NOTE','₵20 Note',20,'note'],['GHS_10_NOTE','₵10 Note',10,'note'],['GHS_5_NOTE','₵5 Note',5,'note'],['GHS_1_NOTE','₵1 Note',1,'note'],['GHS_2_COIN','₵2 Coin',2,'coin'],['GHS_1_COIN','₵1 Coin',1,'coin'],['GHS_050_COIN','50p',.5,'coin'],['GHS_020_COIN','20p',.2,'coin'],['GHS_010_COIN','10p',.1,'coin']];
    function escapeHtml(v){var div=document.createElement('div');div.textContent=v==null?'':v;return div.innerHTML}
    function add(item){var i=rows.querySelectorAll('.denomination-settings-row').length;rows.insertAdjacentHTML('beforeend','<div class="denomination-settings-row"><input type="hidden" name="pos_settings[cash_denominations_v2]['+i+'][code]" value="'+escapeHtml(item[0]||'')+'" data-field="code"><input type="text" class="form-control" name="pos_settings[cash_denominations_v2]['+i+'][label]" value="'+escapeHtml(item[1]||'')+'" data-field="label" required maxlength="80"><input type="number" class="form-control" name="pos_settings[cash_denominations_v2]['+i+'][value]" value="'+escapeHtml(item[2]||'')+'" data-field="value" min="0.0001" step="0.0001" required><select class="form-control" name="pos_settings[cash_denominations_v2]['+i+'][type]" data-field="type"><option value="note" '+(item[3]==='coin'?'':'selected')+'>Note</option><option value="coin" '+(item[3]==='coin'?'selected':'')+'>Coin</option></select><button type="button" class="btn btn-link text-red remove-denomination-row"><i class="fas fa-trash"></i></button></div>')}
    function reindex(){rows.querySelectorAll('.denomination-settings-row').forEach(function(row,i){row.querySelectorAll('[name]').forEach(function(field){field.name=field.name.replace(/cash_denominations_v2\]\[\d+\]/,'cash_denominations_v2]['+i+']')})})}
    document.getElementById('add_denomination_row').addEventListener('click',function(){add(['','',1,'note'])});document.getElementById('load_ghana_denominations').addEventListener('click',function(){if(!window.confirm('Replace the current denomination list with the Ghana preset?'))return;rows.innerHTML='';preset.forEach(add)});rows.addEventListener('click',function(event){var button=event.target.closest('.remove-denomination-row');if(!button)return;button.closest('.denomination-settings-row').remove();reindex()});
})();
</script>
