'use strict';
const {test} = require('node:test');
const assert = require('node:assert/strict');
const {serverUrl, endpoint, monitor, validState} = require('../lib.cjs');
test('server addresses require HTTPS except local development and retain installation subpaths', () => {
    assert.equal(serverUrl('https://pos.example.com/amenador'), 'https://pos.example.com/amenador/');
    assert.equal(endpoint('http://localhost/perryposs/public', 'state'), 'http://localhost/perryposs/public/api/customer-display-companion/state');
    for (const address of ['http://pos.example.com', 'file:///etc/passwd', 'https://user:password@pos.example.com', 'https://pos.example.com/?token=x']) assert.throws(() => serverUrl(address));
});
test('monitor selection prefers saved secondary and never covers cashier unless explicitly allowed', () => {
    const primary = {id: 1}, second = {id: 2}, third = {id: 3};
    assert.equal(monitor([primary, second, third], '3', primary, false), third);
    assert.equal(monitor([primary, second], 'missing', primary, false), second);
    assert.equal(monitor([primary], 'auto', primary, false), null);
    assert.equal(monitor([primary], 'auto', primary, true), primary);
    assert.equal(monitor([primary, second], '1', primary, false), second);
});
test('display transport accepts bounded orders and rejects malformed server data', () => {
    const state = {active: true, connected: true, order: {items: [{name: '<script>test</script>', quantity: '1,234.50', amount: 'GHS 1.00'}], subtotal: '1', total: '1', discount: '0', tax: '0', adjustments: '0'}};
    assert(validState(state));
    assert(validState({active: false, connected: false, order: null}));
    assert(!validState({active: true, connected: true, order: null}));
    assert(!validState({...state, order: {...state.order, items: new Array(251).fill(state.order.items[0])}}));
    assert(!validState({...state, order: {...state.order, items: [{name: 'Item', quantity: {}, amount: '0'}]}}));
});
