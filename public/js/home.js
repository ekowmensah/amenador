$(document).ready(function() {
    if ($('#dashboard_date_filter').length == 1) {
        dateRangeSettings.startDate = moment();
        dateRangeSettings.endDate = moment();
        $('#dashboard_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
            $('#dashboard_date_filter span').html(
                start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
            );
            update_statistics(start.format('YYYY-MM-DD'), end.format('YYYY-MM-DD'));
            if ($('#quotation_table').length && $('#dashboard_location').length) {
                quotation_datatable.ajax.reload();
            }
        });

        update_statistics(moment().format('YYYY-MM-DD'), moment().format('YYYY-MM-DD'));
    }

    $('#dashboard_location').change( function(e) {
        var start = $('#dashboard_date_filter')
                    .data('daterangepicker')
                    .startDate.format('YYYY-MM-DD');

        var end = $('#dashboard_date_filter')
                    .data('daterangepicker')
                    .endDate.format('YYYY-MM-DD');

        update_statistics(start, end);
    });

    //atock alert datatables
    var stock_alert_table = $('#stock_alert_table').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        searching: false,
        scrollY:        "75vh",
        scrollX:        true,
        scrollCollapse: true,
        fixedHeader: false,
        dom: 'Btirp',
        ajax: {
            "url": base_path + '/home/product-stock-alert',
            "data": function ( d ) {
                if ($('#stock_alert_location').length > 0) {
                    d.location_id = $('#stock_alert_location').val();
                }
            }
        },
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#stock_alert_table'));
        },
    });

    $('#stock_alert_location').change( function(){
        stock_alert_table.ajax.reload();
    });
    //payment dues datatables
    purchase_payment_dues_table = $('#purchase_payment_dues_table').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        searching: false,
        scrollY:        "75vh",
        scrollX:        true,
        scrollCollapse: true,
        fixedHeader: false,
        dom: 'Btirp',
        ajax: {
            "url": base_path + '/home/purchase-payment-dues',
            "data": function ( d ) {
                if ($('#purchase_payment_dues_location').length > 0) {
                    d.location_id = $('#purchase_payment_dues_location').val();
                }
            }
        },
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#purchase_payment_dues_table'));
        },
    });

    $('#purchase_payment_dues_location').change( function(){
        purchase_payment_dues_table.ajax.reload();
    });

    //Sales dues datatables
    sales_payment_dues_table = $('#sales_payment_dues_table').DataTable({
        processing: true,
        serverSide: true,
        ordering: false,
        searching: false,
        scrollY:        "75vh",
        scrollX:        true,
        scrollCollapse: true,
        fixedHeader: false,
        dom: 'Btirp',
        ajax: {
            "url": base_path + '/home/sales-payment-dues',
            "data": function ( d ) {
                if ($('#sales_payment_dues_location').length > 0) {
                    d.location_id = $('#sales_payment_dues_location').val();
                }
            }
        },
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#sales_payment_dues_table'));
        },
    });

    $('#sales_payment_dues_location').change( function(){
        sales_payment_dues_table.ajax.reload();
    });

    //Stock expiry report table
    stock_expiry_alert_table = $('#stock_expiry_alert_table').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        scrollY:        "75vh",
        scrollX:        true,
        scrollCollapse: true,
        fixedHeader: false,
        dom: 'Btirp',
        ajax: {
            url: base_path + '/reports/stock-expiry',
            data: function(d) {
                d.exp_date_filter = $('#stock_expiry_alert_days').val();
            },
        },
        order: [[3, 'asc']],
        columns: [
            { data: 'product', name: 'p.name' },
            { data: 'location', name: 'l.name' },
            { data: 'stock_left', name: 'stock_left' },
            { data: 'exp_date', name: 'exp_date' },
        ],
        fnDrawCallback: function(oSettings) {
            __show_date_diff_for_human($('#stock_expiry_alert_table'));
            __currency_convert_recursively($('#stock_expiry_alert_table'));
        },
    });

    if ($('#quotation_table').length) {
        quotation_datatable = $('#quotation_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader:false,
            aaSorting: [[0, 'desc']],
            "ajax": {
                "url": base_path + '/sells/draft-dt?is_quotation=1',
                "data": function ( d ) {
                    if ($('#dashboard_location').length > 0) {
                        d.location_id = $('#dashboard_location').val();
                    }
                }
            },
            columnDefs: [ {
                "targets": 4,
                "orderable": false,
                "searchable": false
            } ],
            columns: [
                { data: 'transaction_date', name: 'transaction_date'  },
                { data: 'invoice_no', name: 'invoice_no'},
                { data: 'name', name: 'contacts.name'},
                { data: 'business_location', name: 'bl.name'},
                { data: 'action', name: 'action'}
            ]            
        });
    }
});

function update_statistics(start, end) {
    var location_id = '';
    if ($('#dashboard_location').length > 0) {
        location_id = $('#dashboard_location').val();
    }
    var data = { start: start, end: end, location_id: location_id };
    load_admin_analytics(data);
    //get purchase details
    var loader = '<i class="fas fa-sync fa-spin fa-fw margin-bottom"></i>';
    $('.total_purchase').html(loader);
    $('.purchase_due').html(loader);
    $('.total_sell').html(loader);
    $('.invoice_due').html(loader);
    $('.total_expense').html(loader);
    $('.total_purchase_return').html(loader);
    $('.total_sell_return').html(loader);
    $('.net').html(loader);
    $.ajax({
        method: 'get',
        url: base_path + '/home/get-totals',
        dataType: 'json',
        data: data,
        success: function(data) {
            //purchase details
            $('.total_purchase').html(__currency_trans_from_en(data.total_purchase, true));
            $('.purchase_due').html(__currency_trans_from_en(data.purchase_due, true));

            //sell details
            $('.total_sell').html(__currency_trans_from_en(data.total_sell, true));
            $('.invoice_due').html(__currency_trans_from_en(data.invoice_due, true));
            //expense details
            $('.total_expense').html(__currency_trans_from_en(data.total_expense, true));
            var total_purchase_return = data.total_purchase_return - data.total_purchase_return_paid;
            $('.total_purchase_return').html(__currency_trans_from_en(total_purchase_return, true));
            var total_sell_return_due = data.total_sell_return - data.total_sell_return_paid;
            $('.total_sell_return').html(__currency_trans_from_en(total_sell_return_due, true));
            $('.total_sr').html(__currency_trans_from_en(data.total_sell_return, true));
            $('.total_srp').html(__currency_trans_from_en(data.total_sell_return_paid, true));
            $('.total_pr').html(__currency_trans_from_en(data.total_purchase_return, true));
            $('.total_prp').html(__currency_trans_from_en(data.total_purchase_return_paid, true));
            $('.net').html(__currency_trans_from_en(data.net, true));

            // assign tooltip total_sell_return 
            var lang = $('#total_srp').data('value');
            var splitlang = lang.split('-');
            
            var newContent = "<p class='mb-0 text-muted fs-10 mt-5'>" + splitlang[0] + ": <span class=''>" + __currency_trans_from_en(data.total_sell_return, true) + "</span><br>" + splitlang[1] + ": <span class=''>" + __currency_trans_from_en(data.total_sell_return_paid, true) + "</span></p>";
            $('#total_srp').attr('data-content', newContent)
            // assign tooltip total_purchase_return 
            var lang = $('#total_prp').data('value');
            var splitlang = lang.split('-');
            
            var newContent = "<p class='mb-0 text-muted fs-10 mt-5'>" + splitlang[0] + ": <span class=''>" + __currency_trans_from_en(data.total_purchase_return, true) + "</span><br>" + splitlang[1] + ": <span class=''>" + __currency_trans_from_en(data.total_purchase_return_paid, true) + "</span></p>";
            
            $('#total_prp').attr('data-content', newContent);

            $('.dashboard-last-updated b').text(moment().format('HH:mm:ss'));

        },
        error: function(xhr) {
            $('.total_purchase, .purchase_due, .total_sell, .invoice_due, .total_expense, .total_purchase_return, .total_sell_return, .net').html('&mdash;');
            if (xhr && xhr.status === 403) {
                toastr.error('You do not have permission to view this dashboard scope.');
            } else {
                toastr.error('Dashboard totals could not be refreshed. Please try again.');
            }
        },
    });
}

var adminAnalyticsRequest = null;

function admin_currency(value) {
    return typeof __currency_trans_from_en === 'function'
        ? __currency_trans_from_en(Number(value || 0), true)
        : Number(value || 0).toLocaleString(undefined, {maximumFractionDigits: 2});
}

function admin_escape(value) {
    return $('<div>').text(value == null ? '' : value).html();
}

function admin_empty(message) {
    return '<div class="admin-empty"><i class="fas fa-chart-bar"></i><span>' + admin_escape(message) + '</span></div>';
}

function admin_bar_chart(target, items, labelKey, valueKey, currency) {
    var node = $(target);
    if (!items || !items.length) { node.html(admin_empty('No activity in this period')); return; }
    var max = Math.max.apply(null, items.map(function (item) { return Number(item[valueKey] || 0); })) || 1;
    node.html(items.map(function (item, index) {
        var value = Number(item[valueKey] || 0);
        var width = Math.max(value > 0 ? 3 : 0, (value / max) * 100);
        var display = currency ? admin_currency(value) : value.toLocaleString();
        return '<div class="admin-bar-row"><div class="admin-bar-label"><span>' + admin_escape(item[labelKey] || 'Unknown') + '</span><b>' + display + '</b></div><div class="admin-bar-track"><span style="width:' + width + '%;--bar-index:' + index + '"></span></div></div>';
    }).join(''));
}

function admin_trend_chart(rows) {
    var node = $('#admin_trend_chart');
    if (!rows || !rows.length) { node.html(admin_empty('No sales or expenses in this period')); return; }
    var width = 760, height = 220, pad = 24;
    var max = Math.max.apply(null, rows.map(function (r) { return Math.max(Number(r.sales || 0), Number(r.expenses || 0)); })) || 1;
    function points(key) {
        return rows.map(function (r, i) {
            var x = pad + (rows.length === 1 ? (width - pad * 2) / 2 : i * ((width - pad * 2) / (rows.length - 1)));
            var y = height - pad - (Number(r[key] || 0) / max) * (height - pad * 2);
            return x.toFixed(1) + ',' + y.toFixed(1);
        }).join(' ');
    }
    var labels = rows.filter(function (_, i) { return i === 0 || i === rows.length - 1 || (rows.length <= 8); }).map(function (r, i, list) {
        var original = rows.indexOf(r), x = pad + (rows.length === 1 ? (width - pad * 2) / 2 : original * ((width - pad * 2) / (rows.length - 1)));
        return '<text x="' + x + '" y="216" text-anchor="middle">' + admin_escape(r.period) + '</text>';
    }).join('');
    node.html('<div class="admin-chart-legend"><span><i class="is-sales"></i>Revenue</span><span><i class="is-expense"></i>Expenses</span></div><svg viewBox="0 0 760 225" preserveAspectRatio="none" role="img"><line x1="24" y1="196" x2="736" y2="196" class="admin-grid-line"/><polyline points="' + points('sales') + '" class="admin-line admin-line--sales"/><polyline points="' + points('expenses') + '" class="admin-line admin-line--expense"/>' + labels + '</svg>');
}

function render_admin_analytics(data) {
    $('#admin_kpi_grid').html((data.kpis || []).map(function (kpi) {
        var changeClass = Number(kpi.change) >= 0 ? 'is-positive' : 'is-negative';
        if (kpi.inverse) { changeClass = Number(kpi.change) <= 0 ? 'is-positive' : 'is-negative'; }
        var value = kpi.currency ? admin_currency(kpi.value) : Number(kpi.value || 0).toLocaleString();
        return '<article class="admin-kpi-card admin-kpi-card--' + admin_escape(kpi.key) + '"><span>' + admin_escape(kpi.label) + '</span><strong>' + value + '</strong><small class="' + changeClass + '"><i class="fas fa-arrow-' + (Number(kpi.change) >= 0 ? 'up' : 'down') + '"></i>' + Math.abs(Number(kpi.change || 0)).toFixed(1) + '% <em>vs previous period</em></small></article>';
    }).join(''));

    admin_trend_chart(data.trend || []);
    admin_bar_chart('#admin_payment_chart', data.payments || [], 'method', 'amount', true);
    var ageingLabels = ['0–30 days', '31–60 days', '61–90 days', '90+ days'];
    admin_bar_chart('#admin_ageing_chart', (data.receivables || []).map(function (v, i) { return {label: ageingLabels[i], amount: v}; }), 'label', 'amount', true);

    $('#admin_locations_body').html((data.locations || []).map(function (row, i) {
        var count = Number(row.transaction_count || 0), sales = Number(row.sales || 0);
        return '<tr><td><span class="admin-rank-number">' + (i + 1) + '</span><b>' + admin_escape(row.name) + '</b></td><td>' + admin_currency(sales) + '</td><td>' + admin_currency(row.expenses) + '</td><td>' + count.toLocaleString() + '</td><td>' + admin_currency(count ? sales / count : 0) + '</td></tr>';
    }).join('') || '<tr><td colspan="5">' + admin_empty('No location activity') + '</td></tr>');

    var inv = data.inventory || {};
    $('#admin_inventory').html('<div><span>Stock value at cost</span><strong>' + admin_currency(inv.cost_value) + '</strong></div><div><span>Potential retail value</span><strong>' + admin_currency(inv.retail_value) + '</strong></div><div><span>Out of stock</span><strong class="is-warning">' + Number(inv.out_of_stock || 0).toLocaleString() + '</strong></div><div><span>Negative stock</span><strong class="is-danger">' + Number(inv.negative_stock || 0).toLocaleString() + '</strong></div>');

    $('#admin_risk_feed').html((data.risk || []).map(function (item) {
        return '<div class="admin-risk admin-risk--' + item.level + '"><i class="fas ' + admin_escape(item.icon) + '"></i><span><b>' + admin_escape(item.label) + '</b><small>' + (item.currency ? admin_currency(item.value) : Number(item.value || 0).toLocaleString()) + '</small></span></div>';
    }).join(''));

    function ranking(target, rows, valueLabel) {
        var max = Math.max.apply(null, (rows || []).map(function (r) { return Number(r.sales || 0); })) || 1;
        $(target).html((rows || []).map(function (row, i) {
            return '<div class="admin-ranking-row"><span>' + (i + 1) + '</span><div><b>' + admin_escape(row.name || 'Unknown') + '</b><small>' + Number(row[valueLabel] || 0).toLocaleString() + (valueLabel === 'quantity' ? ' units' : ' sales') + '</small><i style="width:' + ((Number(row.sales || 0) / max) * 100) + '%"></i></div><strong>' + admin_currency(row.sales) + '</strong></div>';
        }).join('') || admin_empty('No results in this period'));
    }
    ranking('#admin_staff', data.staff || [], 'transaction_count');
    ranking('#admin_products', data.products || [], 'quantity');
    $('#admin_analytics_updated').text('Updated ' + ((data.meta && data.meta.generated_at) || moment().format('HH:mm:ss')));
}

function load_admin_analytics(filters) {
    if (!$('#admin_intelligence').length) { return; }
    if (adminAnalyticsRequest && adminAnalyticsRequest.readyState !== 4) { adminAnalyticsRequest.abort(); }
    $('#admin_analytics_error').prop('hidden', true);
    $('#admin_intelligence').addClass('is-refreshing');
    adminAnalyticsRequest = $.ajax({
        method: 'get', url: base_path + '/home/admin-analytics', dataType: 'json', data: filters || {},
        success: render_admin_analytics,
        error: function (xhr, status) { if (status !== 'abort') { $('#admin_analytics_error').prop('hidden', false); } },
        complete: function () { $('#admin_intelligence').removeClass('is-refreshing'); }
    });
}

$(document).on('click', '#retry_admin_analytics', function () {
    var picker = $('#dashboard_date_filter').data('daterangepicker');
    load_admin_analytics({start: picker ? picker.startDate.format('YYYY-MM-DD') : moment().format('YYYY-MM-DD'), end: picker ? picker.endDate.format('YYYY-MM-DD') : moment().format('YYYY-MM-DD'), location_id: $('#dashboard_location').val() || ''});
});

//Dashboard presentation enhancements. Existing reporting selectors and AJAX
//flows remain untouched; these classes only organise the rendered interface.
$(function () {
    var dashboard = $('.home-dashboard');
    if (!dashboard.length) {
        return;
    }

    var kpis = [
        ['.total_sell', 'dashboard-kpi--sales'],
        ['.net', 'dashboard-kpi--profit'],
        ['.invoice_due', 'dashboard-kpi--receivable'],
        ['.purchase_due', 'dashboard-kpi--payable'],
        ['.total_purchase', 'dashboard-kpi--purchase'],
        ['.total_sell_return', 'dashboard-kpi--returns'],
        ['.total_purchase_return', 'dashboard-kpi--returns'],
        ['.total_expense', 'dashboard-kpi--expense']
    ];

    kpis.forEach(function (item) {
        dashboard.find(item[0]).first().closest('.tw-bg-white').addClass('dashboard-kpi ' + item[1]);
    });

    var mainGrid = dashboard.find('.dashboard-main > .tw-grid').first();
    var chartPanels = dashboard.find('.dashboard-main .tw-h-100').closest('.tw-bg-white');
    chartPanels.addClass('dashboard-panel dashboard-chart-panel');

    var panelTypes = {
        '#sales_payment_dues_table': 'dashboard-attention-panel',
        '#purchase_payment_dues_table': 'dashboard-attention-panel',
        '#stock_alert_table': 'dashboard-attention-panel',
        '#stock_expiry_alert_table': 'dashboard-attention-panel',
        '#sales_order_table': 'dashboard-workflow-panel',
        '#purchase_requisition_table': 'dashboard-workflow-panel',
        '#purchase_order_table': 'dashboard-workflow-panel',
        '#shipments_table': 'dashboard-workflow-panel',
        '#cash_flow_table': 'dashboard-cashflow-panel'
    };

    Object.keys(panelTypes).forEach(function (selector) {
        var table = dashboard.find(selector);
        if (!table.length) {
            return;
        }

        var panel = table.closest('.tw-bg-white');
        panel.addClass('dashboard-panel ' + panelTypes[selector]);
        table.closest('.tw-flow-root').addClass('dashboard-panel-body');

        //The Tailwind gap class contains a dot, which is unreliable in a CSS
        //selector unless it is double escaped. Select the direct card header
        //structurally so every dashboard panel receives a toggle.
        var panelHeader = panel.children('div').children('.tw-flex.tw-items-center').first();
        if (panelHeader.length && !panelHeader.find('.dashboard-panel-toggle').length) {
            panelHeader.append('<button type="button" class="dashboard-panel-toggle" aria-expanded="true" aria-label="Expand or collapse this section" title="Expand or collapse"><i class="fas fa-chevron-down" aria-hidden="true"></i><span>Expand</span></button>');
        }
    });

    function addSectionBefore(target, eyebrow, title, description) {
        if (!target || !target.length || target.prev('.dashboard-section-heading').length) {
            return;
        }
        $('<div class="dashboard-section-heading"><div><span>' + eyebrow + '</span><h2>' + title + '</h2><p>' + description + '</p></div></div>').insertBefore(target.first());
    }

    addSectionBefore(chartPanels.first(), 'Performance', 'Sales performance', 'Understand sales movement across the selected reporting period.');
    addSectionBefore(dashboard.find('#sales_payment_dues_table').closest('.dashboard-panel'), 'Needs attention', 'Attention required', 'Prioritise overdue payments, stock shortages and expiring inventory.');
    addSectionBefore(dashboard.find('#sales_order_table, #purchase_requisition_table, #purchase_order_table, #shipments_table').first().closest('.dashboard-panel'), 'Operations', 'Business activity', 'Follow active orders, purchasing workflows and fulfilment.');
    addSectionBefore(dashboard.find('#cash_flow_table').closest('.dashboard-panel'), 'Finance', 'Cash movement', 'Review payments recovered and account movement.');

    dashboard.on('click', '.dashboard-panel-toggle', function () {
        var button = $(this);
        var panel = button.closest('.dashboard-panel');
        panel.toggleClass('is-collapsed');
        var expanded = !panel.hasClass('is-collapsed');
        button.attr('aria-expanded', expanded ? 'true' : 'false');
        button.find('span').text(expanded ? 'Collapse' : 'Expand');
        if (expanded && $.fn.dataTable) {
            setTimeout(function () {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            }, 30);
        }
    });

    //Keep high-volume operational queues compact on first view.
    [
        '#sales_payment_dues_table',
        '#purchase_payment_dues_table',
        '#stock_alert_table',
        '#sales_order_table',
        '#shipments_table'
    ].forEach(function (selector) {
        dashboard.find(selector).closest('.dashboard-panel').addClass('is-collapsed')
            .find('.dashboard-panel-toggle').attr('aria-expanded', 'false').find('span').text('Expand');
    });
});
