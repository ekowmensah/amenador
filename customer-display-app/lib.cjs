'use strict';

function serverUrl(value) {
    const url = new URL(value);
    const local = ['localhost', '127.0.0.1', '[::1]'].includes(url.hostname);
    if (url.username || url.password || (url.protocol !== 'https:' && !(local && url.protocol === 'http:'))) {
        throw new Error('Use an HTTPS server address, or HTTP on localhost for development.');
    }
    if (url.search || url.hash) throw new Error('Enter the server address without a query or fragment.');
    url.pathname = url.pathname.replace(/\/+$/, '') + '/';
    return url.href;
}

function endpoint(base, action) {
    if (!['pair', 'state', 'screen'].includes(action)) throw new Error('Unknown display endpoint.');
    return new URL('api/customer-display-companion/' + action, serverUrl(base)).href;
}

function monitor(displays, preferred, primary, allowPrimary) {
    const chosen = displays.find(display => String(display.id) === String(preferred));
    if (chosen && (allowPrimary || chosen.id !== primary.id)) return chosen;
    return displays.find(display => display.id !== primary.id) || (allowPrimary ? primary : null);
}

function validState(state) {
    if (!state || typeof state.active !== 'boolean' || typeof state.connected !== 'boolean') return false;
    if (!state.order) return !state.connected;
    const order = state.order;
    return Array.isArray(order.items) && order.items.length <= 250 &&
        order.items.every(item => item && typeof item.name === 'string' && item.name.length <= 500 &&
            typeof item.quantity === 'string' && item.quantity.length <= 80 && typeof item.amount === 'string' && item.amount.length <= 80) &&
        ['subtotal', 'total', 'discount', 'tax', 'adjustments'].every(key => typeof order[key] === 'string' && order[key].length <= 80);
}

module.exports = {serverUrl, endpoint, monitor, validState};
