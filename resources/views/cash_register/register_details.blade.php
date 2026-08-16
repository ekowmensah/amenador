@php
    $openedAt = \Carbon\Carbon::parse($register_details->open_time);
    $asAt = \Carbon\Carbon::parse($close_time);
    $duration = $openedAt->diffForHumans($asAt, true);
    $denominationTotal = 0;
    if (!empty($register_details->denominations)) {
        foreach ($register_details->denominations as $value => $count) {
            if (is_array($count)) {
                $denominationTotal += ((float) ($count['value'] ?? 0) * (float) ($count['count'] ?? 0));
            } else {
                $denominationTotal += ((float) $value * (float) $count);
            }
        }
    }
    $cashVariance = $denominationTotal - $expected_cash;
@endphp

<div class="modal-dialog register-details-dialog" role="document">
<div class="modal-content register-details-workspace">
    <header class="register-details-header">
        <div class="register-details-heading">
            <span class="register-details-icon"><i class="fas fa-cash-register"></i></span>
            <div><span class="register-details-eyebrow">Live register control</span><h3>Register Details</h3><p>{{ $register_details->location_name }} &middot; {{ $register_details->user_name }}</p></div>
        </div>
        <div class="register-details-status"><span class="register-status-pill"><i class="fas fa-circle"></i> {{ ucfirst($register_details->register_status ?? 'open') }}</span><strong>{{ $duration }}</strong><small>{{ $openedAt->format('d M Y, h:i A') }} &ndash; {{ ($register_details->register_status ?? 'open') === 'close' ? $asAt->format('d M Y, h:i A') : 'Now' }}</small></div>
        <button type="button" class="register-details-close no-print" data-dismiss="modal" aria-label="Close">&times;</button>
    </header>

    <nav class="register-details-tabs no-print" aria-label="Register details sections">
        <button type="button" class="is-active" data-register-tab="overview"><i class="fas fa-chart-pie"></i> Overview</button>
        <button type="button" data-register-tab="payments"><i class="fas fa-wallet"></i> Payments</button>
        <button type="button" data-register-tab="products"><i class="fas fa-boxes"></i> Products</button>
        <button type="button" data-register-tab="cash-count"><i class="fas fa-coins"></i> Cash Control</button>
        <button type="button" data-register-tab="audit"><i class="fas fa-clipboard-check"></i> Audit</button>
    </nav>

    <div class="modal-body register-details-body">
        <section class="register-tab-panel is-active" data-register-panel="overview">
            <div class="register-summary-grid">
                <article><span>Opening Cash</span><strong class="display_currency" data-currency_symbol="true">{{ $opening_cash }}</strong><small>Starting float</small></article>
                <article class="is-positive"><span>Gross Sales</span><strong class="display_currency" data-currency_symbol="true">{{ $gross_sales }}</strong><small>Final sales in this session</small></article>
                <article class="is-negative"><span>Refunds</span><strong class="display_currency" data-currency_symbol="true">{{ $register_details->total_refund }}</strong><small>All refunded payments</small></article>
                <article class="is-negative"><span>Expenses</span><strong class="display_currency" data-currency_symbol="true">{{ $register_details->total_expense }}</strong><small>Register expenses</small></article>
                <article><span>Credit Sales</span><strong class="display_currency" data-currency_symbol="true">{{ $credit_sales }}</strong><small>Sales not settled here</small></article>
                @if($can_view_reconciliation)<article class="is-primary"><span>Expected Cash</span><strong class="display_currency" data-currency_symbol="true">{{ $expected_cash }}</strong><small>Opening + cash sales - refunds - expenses</small></article>@endif
            </div>
            <div class="register-kpi-strip">
                <div><span>Transactions</span><strong>{{ $register_kpis['transactions'] }}</strong></div>
                <div><span>Average Sale</span><strong class="display_currency" data-currency_symbol="true">{{ $register_kpis['average_sale'] }}</strong></div>
                <div><span>Items Sold</span><strong>{{ @format_quantity($register_kpis['items_sold']) }}</strong></div>
                <div><span>Refund Events</span><strong>{{ $register_kpis['refunds'] }}</strong></div>
                <div><span>Expense Events</span><strong>{{ $register_kpis['expenses'] }}</strong></div>
            </div>
            @if(count($register_warnings))
                <div class="register-warning-list">
                    @foreach($register_warnings as $warning)<div class="is-{{ $warning['level'] }}"><i class="fas fa-exclamation-triangle"></i><span>{{ $warning['message'] }}</span></div>@endforeach
                </div>
            @endif
            <div class="register-overview-columns">
                <article class="register-detail-card">
                    <header><div><span>Payment snapshot</span><h4>Net collections</h4></div><button type="button" data-open-register-tab="payments">View all</button></header>
                    <div class="register-payment-snapshot">@forelse($payment_breakdown as $payment)<div><span>{{ $payment['label'] }}</span><strong class="display_currency" data-currency_symbol="true">{{ $payment['net'] }}</strong></div>@empty<p class="register-empty-copy">No payment activity yet.</p>@endforelse</div>
                </article>
                <article class="register-detail-card">
                    <header><div><span>Session information</span><h4>Register audit</h4></div><button type="button" data-open-register-tab="audit">View details</button></header>
                    <dl class="register-audit-list"><div><dt>Cashier</dt><dd>{{ $register_details->user_name }}</dd></div><div><dt>Location</dt><dd>{{ $register_details->location_name }}</dd></div><div><dt>Opened</dt><dd>{{ $openedAt->format('d M, h:i A') }}</dd></div><div><dt>As at</dt><dd>{{ $asAt->format('d M, h:i A') }}</dd></div></dl>
                </article>
            </div>
        </section>

        <section class="register-tab-panel" data-register-panel="payments">
            <div class="register-section-heading"><div><span>Reconciliation</span><h4>Payments by method</h4></div><p>Sales minus refunds and expenses equals net collection.</p></div>
            <div class="register-table-wrap"><table class="register-modern-table register-export-table" data-export-name="register-payments"><thead><tr><th>Payment method</th><th>Transactions</th><th>Sales</th><th>Refunds</th><th>Expenses</th><th>Net</th></tr></thead><tbody>
                @forelse($payment_breakdown as $payment)
                    <tr><td><strong>{{ $payment['label'] }}</strong></td><td>{{ $payment['count'] }}</td><td><span class="display_currency" data-currency_symbol="true">{{ $payment['sales'] }}</span></td><td class="is-negative"><span class="display_currency" data-currency_symbol="true">{{ $payment['refunds'] }}</span></td><td class="is-negative"><span class="display_currency" data-currency_symbol="true">{{ $payment['expenses'] }}</span></td><td class="is-net"><span class="display_currency" data-currency_symbol="true">{{ $payment['net'] }}</span></td></tr>
                @empty<tr><td colspan="6" class="register-empty-cell">No payment activity yet.</td></tr>@endforelse
            </tbody></table></div>
            @if(!empty($register_details->payment_confirmations))
                <div class="register-section-heading register-closing-count-heading"><div><span>Manual verification</span><h4>Mobile-money reconciliation</h4></div><p>Confirmed against the provider account at closing.</p></div>
                <div class="register-table-wrap"><table class="register-modern-table"><thead><tr><th>Method</th><th>System total</th><th>Confirmed total</th><th>Variance</th><th>Confirmed at</th></tr></thead><tbody>@foreach($register_details->payment_confirmations as $confirmation)<tr><td>{{ $confirmation['label'] }}</td><td class="display_currency" data-currency_symbol="true">{{ $confirmation['system_total'] }}</td><td class="display_currency" data-currency_symbol="true">{{ $confirmation['confirmed_total'] }}</td><td class="{{ ($confirmation['variance'] ?? 0) < 0 ? 'is-negative' : 'is-net' }}"><span class="display_currency" data-currency_symbol="true">{{ $confirmation['variance'] }}</span></td><td>{{ !empty($confirmation['confirmed_at']) ? \Carbon\Carbon::parse($confirmation['confirmed_at'])->format('d M Y, h:i A') : '—' }}</td></tr>@endforeach</tbody></table></div>
            @endif
        </section>

        <section class="register-tab-panel" data-register-panel="products">
            <div class="register-section-heading"><div><span>Sales mix</span><h4>Products sold</h4></div><label class="register-product-search no-print"><i class="fas fa-search"></i><input type="search" placeholder="Search product or SKU"></label></div>
            <div class="register-product-insights">
                <article><span>Top by revenue</span>@foreach($top_products_by_revenue as $product)<div><strong>{{ $product->product_name }}</strong><em class="display_currency" data-currency_symbol="true">{{ $product->total_amount }}</em></div>@endforeach</article>
                <article><span>Top by quantity</span>@foreach($top_products_by_quantity as $product)<div><strong>{{ $product->product_name }}</strong><em>{{ @format_quantity($product->total_quantity) }}</em></div>@endforeach</article>
            </div>
            <div class="register-table-wrap register-products-table-wrap"><table class="register-modern-table register-products-table register-export-table" data-export-name="register-products"><thead><tr><th>#</th><th>SKU</th><th>Product</th><th>Quantity</th><th>Total</th></tr></thead><tbody>
                @forelse($details['product_details'] as $detail)
                    <tr><td>{{ $loop->iteration }}</td><td>{{ $detail->sku }}</td><td><strong>{{ $detail->product_name }}</strong>@if($detail->product_type === 'variable') <small>{{ $detail->product_variation_name }} - {{ $detail->variation_name }}</small>@endif</td><td>{{ @format_quantity($detail->total_quantity) }}</td><td class="is-net"><span class="display_currency" data-currency_symbol="true">{{ $detail->total_amount }}</span></td></tr>
                @empty<tr><td colspan="5" class="register-empty-cell">No products sold in this session.</td></tr>@endforelse
            </tbody></table></div>
            <details class="register-collapsible"><summary>Sales by brand <i class="fas fa-chevron-down"></i></summary><div class="register-table-wrap"><table class="register-modern-table"><thead><tr><th>Brand</th><th>Quantity</th><th>Total</th></tr></thead><tbody>
                @foreach($details['product_details_by_brand'] as $detail)<tr><td>{{ $detail->brand_name ?: 'Unbranded' }}</td><td>{{ @format_quantity($detail->total_quantity) }}</td><td class="is-net"><span class="display_currency" data-currency_symbol="true">{{ $detail->total_amount }}</span></td></tr>@endforeach
            </tbody></table></div></details>
            @if(!empty($details['types_of_service_details']) && $details['types_of_service_details']->count())
                <details class="register-collapsible"><summary>Sales by service type <i class="fas fa-chevron-down"></i></summary><div class="register-table-wrap"><table class="register-modern-table"><thead><tr><th>Service</th><th>Total</th></tr></thead><tbody>
                    @php $serviceTotal = 0; @endphp
                    @foreach($details['types_of_service_details'] as $detail) @php $serviceTotal += $detail->total_sales; @endphp <tr><td>{{ $detail->types_of_service_name ?: 'Unspecified' }}</td><td class="is-net"><span class="display_currency" data-currency_symbol="true">{{ $detail->total_sales }}</span></td></tr>@endforeach
                </tbody><tfoot><tr><th>Total</th><th><span class="display_currency" data-currency_symbol="true">{{ $serviceTotal }}</span></th></tr></tfoot></table></div></details>
            @endif
        </section>

        <section class="register-tab-panel" data-register-panel="cash-count">
            <div class="register-section-heading"><div><span>Drawer accountability</span><h4>{{ ($register_details->register_status ?? 'open') === 'open' ? 'Live expected cash' : 'Final cash reconciliation' }}</h4></div><p>{{ ($register_details->register_status ?? 'open') === 'open' ? 'Calculated from recorded register activity—not a physical count.' : 'Compare the system expectation with the physical closing count.' }}</p></div>
            <div class="register-live-cash-formula">
                <article><span>Opening float</span><strong class="display_currency" data-currency_symbol="true">{{ $opening_cash }}</strong><small>Counted at opening</small></article><i class="fas fa-plus"></i>
                <article><span>Cash sales</span><strong class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash }}</strong><small>Finalized cash receipts</small></article><i class="fas fa-minus"></i>
                <article class="is-deduction"><span>Cash refunds</span><strong class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_refund }}</strong><small>Returned to customers</small></article><i class="fas fa-minus"></i>
                <article class="is-deduction"><span>Cash expenses</span><strong class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_expense }}</strong><small>Paid from drawer</small></article><i class="fas fa-equals"></i>
                <article class="is-expected"><span>Expected in drawer</span><strong class="display_currency" data-currency_symbol="true">{{ $expected_cash }}</strong><small>System-calculated balance</small></article>
            </div>
            @if(($register_details->register_status ?? 'open') === 'open')<div class="register-live-cash-notice"><i class="fas fa-info-circle"></i><div><strong>This is not a physical cash count</strong><span>The actual drawer amount is only known after the cashier counts it during register closing.</span></div></div>@endif
            @if(!empty($register_details->opening_denominations))
                <details class="register-collapsible register-opening-count-card"><summary><span><small>Starting audit record</small> Opening denomination breakdown</span><strong class="display_currency" data-currency_symbol="true">{{ $opening_cash }}</strong><i class="fas fa-chevron-down"></i></summary><div class="register-denomination-grid">@foreach($register_details->opening_denominations as $item)<div><span>{{ $item['label'] ?? $item['code'] }} &times; {{ $item['count'] ?? 0 }}</span><strong>@format_currency(($item['value'] ?? 0) * ($item['count'] ?? 0))</strong></div>@endforeach</div></details>
            @else
                <div class="register-register-legacy-note"><i class="fas fa-history"></i> This register was opened before opening denomination counts were enabled.</div>
            @endif
            @if(!empty($register_details->denominations))
                <div class="register-section-heading register-closing-count-heading"><div><span>Physical closing checkpoint</span><h4>Closing drawer count</h4></div><p>Entered by the cashier during closing.</p></div>
                <div class="register-cash-layout"><div class="register-denomination-grid">@foreach($register_details->denominations as $value => $count) @php $isStructured = is_array($count); $denominationLabel = $isStructured ? ($count['label'] ?? $value) : $value; $denominationValue = $isStructured ? ($count['value'] ?? 0) : $value; $denominationCount = $isStructured ? ($count['count'] ?? 0) : $count; @endphp <div><span>{{ $denominationLabel }} &times; {{ $denominationCount }}</span><strong>@format_currency($denominationValue * $denominationCount)</strong></div>@endforeach</div>
                @if($can_view_reconciliation)<aside class="register-reconciliation-card"><div><span>Expected cash</span><strong class="display_currency" data-currency_symbol="true">{{ $expected_cash }}</strong></div><div><span>Physically counted</span><strong class="display_currency" data-currency_symbol="true">{{ $denominationTotal }}</strong></div><div class="{{ $cashVariance < 0 ? 'is-short' : ($cashVariance > 0 ? 'is-over' : 'is-balanced') }}"><span>{{ $cashVariance < 0 ? 'Short' : ($cashVariance > 0 ? 'Over' : 'Balanced') }}</span><strong class="display_currency" data-currency_symbol="true">{{ abs($cashVariance) }}</strong></div></aside>@endif</div>
            @elseif(($register_details->register_status ?? 'open') !== 'open')<div class="register-register-legacy-note"><i class="fas fa-exclamation-triangle"></i> No physical closing denomination count was recorded.</div>@endif
        </section>

        <section class="register-tab-panel" data-register-panel="audit">
            <div class="register-audit-grid"><article><span>Register ID</span><strong>#{{ $register_details->register_id }}</strong></article><article><span>Status</span><strong>{{ ucfirst($register_details->register_status ?? 'open') }}</strong></article><article><span>Cashier</span><strong>{{ $register_details->user_name }}</strong><small>{{ $register_details->email }}</small></article><article><span>Location</span><strong>{{ $register_details->location_name }}</strong></article><article><span>Opened at</span><strong>{{ $openedAt->format('d M Y, h:i:s A') }}</strong></article><article><span>Report generated</span><strong>{{ $asAt->format('d M Y, h:i:s A') }}</strong></article></div>
            @if(!empty($register_details->closing_note))<div class="register-note"><span>Closing note</span><p>{{ $register_details->closing_note }}</p></div>@endif
            <div class="register-section-heading register-activity-heading"><div><span>Ledger trail</span><h4>Recent register activity</h4></div><p>Latest 12 register events.</p></div>
            <div class="register-activity-list">
                @forelse($recent_activity as $activity)
                    <article><time>{{ \Carbon\Carbon::parse($activity->created_at)->format('h:i:s A') }}</time><span class="register-activity-type is-{{ $activity->transaction_type }}">{{ ucfirst(str_replace('_', ' ', $activity->transaction_type)) }}</span><div><strong>{{ $activity->invoice_no ?: ($activity->ref_no ?: 'Register entry') }}</strong><small>{{ $activity->customer_name ?: ucfirst(str_replace('_', ' ', $activity->pay_method ?: 'cash')) }}</small></div><em class="display_currency" data-currency_symbol="true">{{ $activity->amount }}</em></article>
                @empty<div class="register-empty-state"><p>No register activity recorded.</p></div>@endforelse
            </div>
        </section>
    </div>

    <footer class="modal-footer register-details-footer no-print"><span><i class="fas fa-shield-alt"></i> Updated {{ $asAt->format('h:i:s A') }} <label><input type="checkbox" class="register-auto-refresh"> Auto refresh</label></span><div><button type="button" class="register-footer-button" data-register-export="register-payments"><i class="fas fa-file-csv"></i> Payments CSV</button><button type="button" class="register-footer-button" data-register-export="register-products"><i class="fas fa-file-csv"></i> Products CSV</button><button type="button" class="register-footer-button" onclick="$('.register_details_modal').one('hidden.bs.modal', function(){ $('#register_details').trigger('click'); }).modal('hide');"><i class="fas fa-sync-alt"></i> Refresh</button><button type="button" class="register-footer-button" data-register-print><i class="fas fa-print"></i> Print</button><button type="button" class="register-footer-button is-primary" data-dismiss="modal">Close</button></div></footer>
</div>
</div>

<script>
(function () {
    var modal = $('.register_details_modal');
    window.clearInterval(window.posRegisterAutoRefreshTimer);
    modal.off('.registerWorkspace');
    modal.on('click.registerWorkspace', '[data-register-tab], [data-open-register-tab]', function () {
        var tab = $(this).data('register-tab') || $(this).data('open-register-tab');
        window.posRegisterActiveTab = tab;
        modal.find('[data-register-tab]').removeClass('is-active').filter('[data-register-tab="' + tab + '"]').addClass('is-active');
        modal.find('[data-register-panel]').removeClass('is-active').filter('[data-register-panel="' + tab + '"]').addClass('is-active');
    });
    modal.on('input.registerWorkspace', '.register-product-search input', function () {
        var term = $(this).val().toLowerCase();
        modal.find('.register-products-table tbody tr').each(function () { $(this).toggle(!term || $(this).text().toLowerCase().indexOf(term) !== -1); });
    });
    modal.on('click.registerWorkspace', '[data-register-export]', function () {
        var name = $(this).data('register-export');
        var rows = [];
        modal.find('table[data-export-name="' + name + '"] tr').each(function () {
            var cells = [];
            $(this).find('th,td').each(function () { cells.push('"' + $(this).text().trim().replace(/"/g, '""') + '"'); });
            if (cells.length) { rows.push(cells.join(',')); }
        });
        var blob = new Blob([rows.join('\r\n')], {type: 'text/csv;charset=utf-8;'});
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = name + '-{{ $asAt->format('Ymd-His') }}.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    });
    modal.on('click.registerWorkspace', '[data-register-print]', function () {
        var workspace = modal.find('.register-details-workspace');
        workspace.find('details.register-collapsible').prop('open', true);
        workspace.printThis({
            importCSS: true,
            printDelay: 500,
            pageTitle: @json('Register Details - ' . $register_details->location_name)
        });
    });
    function refreshRegisterDetails() {
        if (!modal.hasClass('in') && !modal.is(':visible')) { return; }
        modal.one('hidden.bs.modal', function () { $('#register_details').trigger('click'); }).modal('hide');
    }
    modal.on('change.registerWorkspace', '.register-auto-refresh', function () {
        window.posRegisterAutoRefreshEnabled = this.checked;
        window.clearInterval(window.posRegisterAutoRefreshTimer);
        if (this.checked) { window.posRegisterAutoRefreshTimer = window.setInterval(refreshRegisterDetails, 60000); }
    });
    if (window.posRegisterActiveTab) { modal.find('[data-register-tab="' + window.posRegisterActiveTab + '"]').trigger('click'); }
    if (window.posRegisterAutoRefreshEnabled) { modal.find('.register-auto-refresh').prop('checked', true).trigger('change'); }
    __currency_convert_recursively(modal);
})();
</script>
