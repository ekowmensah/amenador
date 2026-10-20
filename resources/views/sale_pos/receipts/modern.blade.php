@php
    $invoice = ($modern_paper ?? 'receipt') === 'invoice';
    $showPrices = empty($receipt_details->hide_price);
    $r = $receipt_details;
@endphp
@include('sale_pos.receipts.partial.modern_styles')
<article class="modern-document {{ $invoice ? 'modern-document--invoice' : 'modern-document--receipt' }}" dir="{{ in_array(app()->getLocale(), config('constants.langs_rtl', [])) ? 'rtl' : 'ltr' }}">
    <header class="modern-header">
        @if (!empty($r->letter_head))
            <img class="modern-letterhead" src="{{ $r->letter_head }}" alt="Letterhead">
        @else
            @if (!empty($r->logo))<img class="modern-logo" src="{{ $r->logo }}" alt="Business logo">@endif
            @if (!empty($r->display_name))<h1>{{ $r->display_name }}</h1>@endif
            @if (!empty($r->header_text))<div class="modern-header-copy">{!! $r->header_text !!}</div>@endif
            @foreach (['address', 'contact'] as $field)
                @if (!empty($r->$field))<div class="modern-muted">{!! $r->$field !!}</div>@endif
            @endforeach
            @if (!empty($r->location_custom_fields))<div class="modern-muted">{{ $r->location_custom_fields }}</div>@endif
            @foreach (range(1, 5) as $i)
                @if (!empty($r->{'sub_heading_line'.$i}))<div>{{ $r->{'sub_heading_line'.$i} }}</div>@endif
            @endforeach
            @foreach ([1, 2] as $i)
                @if (!empty($r->{'tax_info'.$i}))<div><b>{{ $r->{'tax_label'.$i} ?? '' }}</b> {{ $r->{'tax_info'.$i} }}</div>@endif
                @if (!empty($r->{'code_'.$i}))<div><b>{{ $r->{'code_label_'.$i} ?? '' }}</b> {{ $r->{'code_'.$i} }}</div>@endif
            @endforeach
        @endif
    </header>
    <div class="modern-title">{!! $r->invoice_heading ?? ($invoice ? 'Invoice' : 'Receipt') !!}</div>
    <table class="modern-meta">
        <tbody>
            <tr><th>{!! $r->invoice_no_prefix ?? 'Receipt no.' !!}</th><td>{{ $r->invoice_no ?? '' }}</td></tr>
            <tr><th>{!! $r->date_label ?? 'Date' !!}</th><td>{{ $r->invoice_date ?? '' }}</td></tr>
            @if (!empty($r->parent_invoice_no))<tr><th>{!! $r->parent_invoice_no_prefix ?? 'Original invoice' !!}</th><td>{{ $r->parent_invoice_no }}</td></tr>@endif
            @foreach (['due_date' => 'Due date', 'customer_name' => 'Customer', 'client_id' => 'Customer ID', 'customer_tax_number' => 'Customer tax ID', 'sales_person' => 'Served by', 'commission_agent' => 'Sales agent', 'table' => 'Table', 'service_staff' => 'Server', 'types_of_service' => 'Service', 'sale_orders_invoice_no' => 'Order no.', 'sale_orders_invoice_date' => 'Order date'] as $field => $fallback)
                @if (!empty($r->$field))
                    @php $labelField = ['customer_name' => 'customer_label', 'customer_tax_number' => 'customer_tax_label', 'sale_orders_invoice_no' => 'sale_orders_invoice_no_label'][$field] ?? $field.'_label'; @endphp
                    <tr><th>{!! $r->$labelField ?? $fallback !!}</th><td>{{ $r->$field }}</td></tr>
                @endif
            @endforeach
            @foreach (($r->types_of_service_custom_fields ?? []) as $label => $value)
                @if ($value !== null && $value !== '')<tr><th>{{ $label }}</th><td>{{ $value }}</td></tr>@endif
            @endforeach
            @if (!empty($r->customer_rp_label))<tr><th>{!! $r->customer_rp_label !!}</th><td>{{ $r->customer_total_rp ?? '' }}</td></tr>@endif
            @foreach (range(1, 5) as $i)
                @foreach (['sell_custom_field_', 'shipping_custom_field_'] as $prefix)
                    @if (!empty($r->{$prefix.$i.'_value'}))<tr><th>{{ $r->{$prefix.$i.'_label'} ?? '' }}</th><td>{!! $r->{$prefix.$i.'_value'} !!}</td></tr>@endif
                @endforeach
            @endforeach
        </tbody>
    </table>
    @if (!empty($r->customer_info))<div class="modern-customer-info">{!! $r->customer_info !!}</div>@endif
    @if (!empty($r->customer_custom_fields))<div class="modern-customer-info">{{ $r->customer_custom_fields }}</div>@endif
    @if (!empty($r->repair_checklist_label) || !empty($r->repair_model))
        @includeIf('sale_pos.receipts.partial.common_repair_invoice')
    @endif
    <table class="modern-items">
        <thead><tr>
            <th>{{ $r->table_product_label ?? 'Item' }}</th>
            <th class="modern-qty">{{ $r->table_qty_label ?? 'Qty' }}</th>
            @if ($showPrices)
                @if ($invoice)<th class="modern-unit">{{ $r->table_unit_price_label ?? 'Unit price' }}</th>@endif
                <th class="modern-amount">{{ $r->table_subtotal_label ?? 'Amount' }}</th>
            @endif
        </tr></thead>
        <tbody>
            @foreach (($r->lines ?? []) as $line)
                <tr class="modern-item">
                    <td>
                        @if (!empty($line['image']))<img class="modern-item-image" src="{{ $line['image'] }}" alt="">@endif
                        <strong class="modern-item-name">{{ $line['name'] ?? '' }} {{ $line['product_variation'] ?? '' }} {{ $line['variation'] ?? '' }}</strong>
                        @if (!$invoice && $showPrices)<div class="modern-item-detail">{{ $r->table_unit_price_label ?? 'Unit price' }}: {{ $line['unit_price_inc_tax'] ?? $line['unit_price_before_discount'] ?? '' }}</div>@endif
                        @foreach (['sub_sku', 'brand', 'cat_code', 'product_custom_fields'] as $field)
                            @if (!empty($line[$field]))<div class="modern-item-detail">{{ $line[$field] }}</div>@endif
                        @endforeach
                        @foreach (['product_description', 'sell_line_note'] as $field)
                            @if (!empty($line[$field]))<div class="modern-item-detail">{!! $line[$field] !!}</div>@endif
                        @endforeach
                        @foreach (['lot_number' => 'Lot', 'product_expiry' => 'Expiry'] as $field => $fallback)
                            @if (!empty($line[$field]))<div class="modern-item-detail">{{ $line[$field.'_label'] ?? $fallback }}: {{ $line[$field] }}</div>@endif
                        @endforeach
                        @if (!empty($line['warranty_name']))<div class="modern-item-detail">{{ $line['warranty_name'] }} {{ $line['warranty_description'] ?? '' }} @if (!empty($line['warranty_exp_date'])){{ @format_date($line['warranty_exp_date']) }}@endif</div>@endif
                        @if ($showPrices && !empty($r->item_discount_label) && !empty($line['total_line_discount']))<div class="modern-item-detail">{{ $r->item_discount_label }}: {{ $line['total_line_discount'] }} @if (!empty($line['line_discount_percent']))({{ $line['line_discount_percent'] }}%)@endif</div>@endif
                        @if ($showPrices && !empty($r->discounted_unit_price_label))<div class="modern-item-detail">{{ $r->discounted_unit_price_label }}: {{ $line['unit_price_inc_tax'] ?? '' }}</div>@endif
                        @if (!empty($r->show_base_unit_details) && !empty($line['quantity']) && ($line['base_unit_multiplier'] ?? 1) != 1)
                            <div class="modern-item-detail">1 {{ $line['units'] ?? '' }} = {{ $line['base_unit_multiplier'] }} {{ $line['base_unit_name'] ?? '' }}; {{ $line['orig_quantity'] ?? '' }} {{ $line['base_unit_name'] ?? '' }}</div>
                        @endif
                    </td>
                    <td class="modern-qty">{{ $line['quantity'] ?? '' }} <small>{{ \App\Utils\ReceiptUnitUtil::label($line['units'] ?? '', (float) ($line['quantity_uf'] ?? $line['quantity'] ?? 0)) }}</small></td>
                    @if ($showPrices)
                        @if ($invoice)<td class="modern-unit">{{ $line['unit_price_before_discount'] ?? $line['unit_price_inc_tax'] ?? '' }}</td>@endif
                        <td class="modern-amount">{{ $line['line_total'] ?? '' }}</td>
                    @endif
                </tr>
                @foreach (($line['modifiers'] ?? []) as $modifier)
                    <tr class="modern-modifier"><td>+ {{ $modifier['name'] ?? '' }} {{ $modifier['variation'] ?? '' }} @if (!empty($modifier['sell_line_note']))<div class="modern-item-detail">{!! $modifier['sell_line_note'] !!}</div>@endif</td><td class="modern-qty">{{ $modifier['quantity'] ?? '' }} <small>{{ \App\Utils\ReceiptUnitUtil::label($modifier['units'] ?? '', (float) ($modifier['quantity_uf'] ?? $modifier['quantity'] ?? 0)) }}</small></td>
                        @if ($showPrices)@if ($invoice)<td class="modern-unit">{{ $modifier['unit_price_inc_tax'] ?? '' }}</td>@endif<td class="modern-amount">{{ $modifier['line_total'] ?? '' }}</td>@endif
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    <div class="modern-summary">
        <table class="modern-totals"><tbody>
            @foreach (['total_items' => 'Items', 'total_quantity' => 'Quantity'] as $field => $label)
                @if (!empty($r->{$field.'_label'}))<tr><th>{!! $r->{$field.'_label'} !!}</th><td>{{ $r->$field ?? '' }}</td></tr>@endif
            @endforeach
            @if ($showPrices)
                <tr><th>{!! $r->subtotal_label ?? 'Subtotal' !!}</th><td>{{ $r->subtotal ?? '' }}</td></tr>
                @if (!empty($r->total_exempt_uf))<tr><th>@lang('lang_v1.exempt')</th><td>{{ $r->total_exempt ?? '' }}</td></tr>@endif
                @foreach (['shipping_charges' => 'Shipping', 'packing_charge' => 'Packing', 'discount' => 'Discount', 'total_line_discount' => 'Item discounts', 'reward_point_amount' => 'Rewards', 'tax' => 'Tax'] as $field => $label)
                    @if (!empty($r->$field))
                        @php $labelField = ['total_line_discount' => 'line_discount_label', 'reward_point_amount' => 'reward_point_label'][$field] ?? $field.'_label'; @endphp
                        <tr><th>{!! $r->$labelField ?? $label !!}</th><td>{{ in_array($field, ['discount', 'total_line_discount', 'reward_point_amount']) ? '(-) ' : '' }}{{ $r->$field }}</td></tr>
                    @endif
                @endforeach
                @foreach (($r->additional_expenses ?? []) as $label => $amount)<tr><th>{{ $label }}</th><td>{{ $amount }}</td></tr>@endforeach
                @if (!empty($r->round_off_amount))<tr><th>{!! $r->round_off_label ?? 'Rounding' !!}</th><td>{{ $r->round_off ?? '' }}</td></tr>@endif
                <tr class="modern-grand-total"><th>{!! $r->total_label ?? 'Total' !!}</th><td>{{ $r->total ?? '' }}</td></tr>
            @endif
        </tbody></table>
        @if ($showPrices && !empty($r->total_in_words))<div class="modern-words">{{ $r->total_in_words }}</div>@endif
    </div>
    @if ($showPrices)
        <table class="modern-payments"><tbody>
            @foreach (($r->payments ?? []) as $payment)<tr><th>{{ $payment['method'] ?? '' }}@if ($invoice && !empty($payment['date']))<small>{{ $payment['date'] }}</small>@endif</th><td>{{ $payment['amount'] ?? '' }}</td></tr>@endforeach
            @foreach (['total_paid' => 'Paid', 'total_due' => 'Balance due', 'all_due' => 'Account balance'] as $field => $fallback)
                @php $labelField = $field === 'all_due' ? 'all_bal_label' : $field.'_label'; @endphp
                @if (isset($r->$field) && !empty($r->$labelField))<tr class="modern-payment-summary"><th>{!! $r->$labelField !!}</th><td>{{ $r->$field }}</td></tr>@endif
            @endforeach
        </tbody></table>
        @if (!empty($r->tax_summary_label) && !empty($r->taxes))
            <div class="modern-tax-summary"><h3>{{ $r->tax_summary_label }}</h3><table class="modern-totals"><tbody>
                @foreach ($r->taxes as $label => $amount)<tr><th>{{ $label }}</th><td>{{ $amount }}</td></tr>@endforeach
            </tbody></table></div>
        @endif
    @endif
    @if (!empty($r->additional_notes))<div class="modern-notes">{!! nl2br($r->additional_notes) !!}</div>@endif
    <footer class="modern-footer">
        @if (!empty($r->show_barcode) || (!empty($r->show_qr_code) && !empty($r->qr_code_text)))
            <div class="modern-codes">
                @if (!empty($r->show_barcode))<img class="modern-barcode" alt="Invoice barcode" src="data:image/png;base64,{{ DNS1D::getBarcodePNG($r->invoice_no, 'C128', 2, 35, [0, 0, 0], true) }}">@endif
                @if (!empty($r->show_qr_code) && !empty($r->qr_code_text))<img class="modern-qr" alt="Invoice QR code" src="data:image/png;base64,{{ DNS2D::getBarcodePNG($r->qr_code_text, 'QRCODE', 3, 3, [0, 0, 0]) }}">@endif
            </div>
        @endif
        @if (!empty($r->footer_text))<div>{!! $r->footer_text !!}</div>@else<p>Thank you for your business.</p>@endif
        <div class="modern-reference">{{ $r->invoice_no ?? '' }}</div>
    </footer>
</article>
