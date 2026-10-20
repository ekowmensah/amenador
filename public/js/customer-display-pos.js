(function ($) {
    'use strict';
    $(function () {
        var button = document.getElementById('open-customer-display');
        if (!button) return;
        var autoOpenSetting = document.getElementById('customer-display-auto-open');
        var autoOpen = !autoOpenSetting || autoOpenSetting.value === '1';
        var display = null;
        var screenDetails = null;
        var shuttingDown = false;
        var windowName = 'customer-display-' + Date.now() + '-' + Math.random().toString(36).slice(2);
        function targetScreen() {
            if (!screenDetails) return window.screen;
            return Array.from(screenDetails.screens).find(function (screen) {
                return screen !== screenDetails.currentScreen;
            }) || screenDetails.currentScreen;
        }
        function placeDisplay() {
            if (!display || display.closed) return;
            var screen = targetScreen();
            try {
                display.moveTo(screen.availLeft || 0, screen.availTop || 0);
                display.resizeTo(screen.availWidth, screen.availHeight);
                display.postMessage({type: 'pos-customer-fullscreen'}, window.location.origin);
            } catch (error) { /* A browser may restrict window placement. */ }
        }
        async function detectScreens(interactive) {
            if (!window.getScreenDetails) return;
            try {
                if (!interactive) {
                    var permission = await navigator.permissions.query({name: 'window-management'});
                    if (permission.state !== 'granted') return;
                }
                if (!screenDetails) {
                    screenDetails = await window.getScreenDetails();
                    screenDetails.addEventListener('screenschange', placeDisplay);
                }
                placeDisplay();
            } catch (error) { /* Keep a full-size window if access is denied. */ }
        }
        function openDisplay(interactive) {
            if (shuttingDown) return;
            if (!display || display.closed) {
                var screen = targetScreen();
                display = window.open(button.dataset.url, windowName,
                    'popup=yes,fullscreen=yes,left=' + (screen.availLeft || 0) + ',top=' + (screen.availTop || 0) +
                    ',width=' + screen.availWidth + ',height=' + screen.availHeight);
            }
            if (!display) {
                button.title = 'Allow pop-ups, then click to enable the customer display';
                if (interactive) toastr.error('Allow pop-ups for this site to open the customer display automatically.');
                return;
            }
            button.title = 'Customer display: click to set up the second screen';
            if (interactive) display.focus();
            placeDisplay();
            send();
        }
        function number(selector, root) {
            var element = (root || $(document)).find(selector).first();
            if (!element.length) return 0;
            var value = element.is('input') ? __read_number(element) : __number_uf(element.text());
            return Number.isFinite(Number(value)) ? Number(value) : 0;
        }
        function money(value) { return __currency_trans_from_en(value, true); }
        function send() {
            if (!display || display.closed) return;
            var items = [];
            $('#pos_table tbody tr.product_row').each(function () {
                var row = $(this);
                var name = row.find('.pos-cart-product-name').first().text().trim();
                items.push({name: name || 'Item', quantity: __number_f(number('input.pos_quantity', row)), amount: money(number('input.pos_line_total', row))});
                row.find('.product_modifier').each(function () {
                    var modifier = $(this);
                    var label = modifier.clone().children().remove().end().text().split('(')[0].trim();
                    items.push({name: '+ ' + label, quantity: __number_f(number('input.modifiers_quantity', modifier)), amount: money(number('input.modifiers_price', modifier) * number('input.modifiers_quantity', modifier))});
                });
            });
            var location = $('#select_location_id option:selected').text().trim() || $('.clean-pos-location-name').text().trim();
            display.postMessage({type: 'pos-customer-order', order: {
                items: items, location: location,
                subtotal: money(number('.price_total')),
                discount: money(number('#total_discount')),
                tax: money(number('#order_tax')),
                has_discount: items.length > 0 && number('#total_discount') !== 0,
                has_tax: items.length > 0 && number('#order_tax') !== 0,
                has_adjustments: items.length > 0 && Math.abs(number('#final_total_input') - number('.price_total') + number('#total_discount') - number('#order_tax')) > 0.005,
                total: items.length ? money(number('#final_total_input')) : money(0),
                adjustments: money(items.length ? number('#final_total_input') - number('.price_total') + number('#total_discount') - number('#order_tax') : 0)
            }}, window.location.origin);
        }
        button.addEventListener('click', function () {
            openDisplay(true);
            detectScreens(true);
        });
        window.addEventListener('message', function (event) {
            if (event.origin === window.location.origin && event.source === display && event.data && event.data.type === 'pos-customer-ready') {
                send();
                if (!event.data.configured) placeDisplay();
            }
        });
        $(document).on('invoice_total_calculated', function () { setTimeout(send, 0); });
        // Also catches programmatic value changes, removals and form resets.
        window.setInterval(send, 500);
        window.addEventListener('pagehide', function () {
            shuttingDown = true;
            if (display && !display.closed) display.close();
        });
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                shuttingDown = false;
                if (autoOpen) { openDisplay(false); detectScreens(false); }
            }
        });
        if (autoOpen) {
            openDisplay(false);
            detectScreens(false);
        }
    });
})(jQuery);
