(() => {
    'use strict';
    const form = document.getElementById('sf-checkout-form');
    if (!form) return;
    const update = () => {
        const delivery = form.querySelector('[name="fulfilment"]:checked')?.value === 'delivery';
        document.getElementById('sf-address').hidden = !delivery;
        form.querySelectorAll('[name="address"], [name="city"]').forEach(el => { el.required = delivery; });
        document.getElementById('sf-delivery-price').textContent = delivery ? storefrontCheckout.delivery : storefrontCheckout.zero;
        document.getElementById('sf-checkout-total').textContent = delivery ? storefrontCheckout.withDelivery : storefrontCheckout.subtotal;
        const online = form.querySelector('[name="payment_method"]:checked')?.value === 'paystack';
        document.getElementById('sf-place-order').textContent = online ? 'Place order & continue to payment' : 'Place order';
    };
    form.addEventListener('change', update);
    form.addEventListener('submit', () => {
        const button = document.getElementById('sf-place-order');
        button.disabled = true;
        button.textContent = 'Creating your order…';
    });
    window.addEventListener('pageshow', () => { document.getElementById('sf-place-order').disabled = !form.querySelector('[name="payment_method"]:checked'); update(); });
    update();
})();
