(function () {
    'use strict';
    var lastUpdate = 0;
    var previous = '';
    var configured = false;
    var fullscreenAttempted = false;
    var byId = function (id) { return document.getElementById(id); };
    function clear() {
        byId('order-items').replaceChildren();
        byId('empty-order').hidden = false;
        byId('item-count').textContent = '0 items';
        ['subtotal', 'total'].forEach(function (key) { byId('order-' + key).textContent = '\u2014'; });
        ['discount', 'tax', 'adjustments'].forEach(function (key) { byId(key + '-row').hidden = true; });
        byId('connection-status').textContent = 'Waiting for register';
        previous = '';
    }
    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin || !window.opener || event.source !== window.opener || !event.data) return;
        if (event.data.type === 'pos-customer-clear') { clear(); return; }
        if (event.data.type === 'pos-customer-fullscreen') {
            configured = true;
            enterFullscreen();
            return;
        }
        if (event.data.type !== 'pos-customer-order') return;
        renderOrder(event.data.order);
    });
    function renderOrder(order) {
        if (!order || !Array.isArray(order.items)) return;
        lastUpdate = Date.now();
        byId('connection-status').textContent = 'Connected to register';
        var signature = JSON.stringify(order);
        if (signature === previous) return;
        previous = signature;
        byId('display-location').textContent = order.location || document.body.dataset.context || '';
        var fragment = document.createDocumentFragment();
        order.items.forEach(function (item, index) {
            var row = document.createElement('div'); row.className = 'order-item';
            var name = document.createElement('div');
            var sequence = document.createElement('small'); sequence.textContent = String(index + 1).padStart(2, '0');
            var label = document.createElement('strong'); label.textContent = item.name;
            name.append(sequence, label); row.append(name);
            [item.quantity, item.amount].forEach(function (value) { var cell = document.createElement('span'); cell.textContent = value; row.append(cell); });
            fragment.append(row);
        });
        byId('order-items').replaceChildren(fragment);
        byId('empty-order').hidden = order.items.length > 0;
        byId('item-count').textContent = order.items.length + (order.items.length === 1 ? ' item' : ' items');
        ['subtotal', 'total', 'discount', 'tax', 'adjustments'].forEach(function (key) { byId('order-' + key).textContent = order[key]; });
        ['discount', 'tax', 'adjustments'].forEach(function (key) { byId(key + '-row').hidden = !order['has_' + key]; });
        byId('order-items').scrollTop = byId('order-items').scrollHeight;
    }
    async function enterFullscreen(interactive) {
        if (document.fullscreenElement || !document.documentElement.requestFullscreen) return;
        if (fullscreenAttempted && !interactive) return;
        fullscreenAttempted = true;
        try {
            var options = {navigationUI: 'hide'};
            if (window.getScreenDetails) {
                var permission = await navigator.permissions.query({name: 'window-management'});
                if (permission.state === 'granted') {
                    var host = window.opener && !window.opener.closed ? window.opener : window;
                    var details = await host.getScreenDetails();
                    options.screen = host === window ? details.currentScreen :
                        Array.from(details.screens).find(function (screen) {
                            return screen !== details.currentScreen;
                        }) || details.currentScreen;
                }
            }
            await document.documentElement.requestFullscreen(options);
        } catch (error) {
            byId('display-fullscreen').title = 'Click to enter full screen';
        }
    }
    byId('display-fullscreen').addEventListener('click', function () { enterFullscreen(true); });
    document.addEventListener('fullscreenchange', function () {
        byId('display-fullscreen').hidden = !!document.fullscreenElement;
    });
    function requestOrder() {
        if (window.opener && window.opener.closed) { window.close(); return; }
        if (window.opener) window.opener.postMessage({type: 'pos-customer-ready', configured: configured}, window.location.origin);
        if (lastUpdate && Date.now() - lastUpdate > 5000) { clear(); lastUpdate = 0; }
    }
    if (window.amenadorDisplay) {
        byId('display-fullscreen').hidden = true;
        window.amenadorDisplay.onState(function (state) {
            if (!state.connected || !state.order) {
                clear();
                byId('connection-status').textContent = state.connected ? 'Waiting for register' : 'Disconnected — waiting to reconnect';
                return;
            }
            renderOrder(state.order);
        });
        window.amenadorDisplay.ready();
    } else {
        requestOrder();
        window.setInterval(requestOrder, 1000);
    }
})();
