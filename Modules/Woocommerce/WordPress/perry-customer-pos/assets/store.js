/* The server owns cart prices, totals and inventory. No order data lives in localStorage. */
(() => {
    'use strict';
    const app = document.getElementById('pcp-app');
    if (!app || !window.perryPOS) return;
    const $ = (selector) => app.querySelector(selector);
    const $$ = (selector) => [...app.querySelectorAll(selector)];
    const products = $$('.pcp-product');
    const empty = $('[data-cart-items]').innerHTML;
    const plusIcon = $('[data-add]')?.innerHTML || '+';
    const closeIcon = $('[data-close-cart].pcp-close-cart').innerHTML;
    let activeCategory = 'all';
    let state = null;
    let busy = false;
    let failed = false;
    let toastTimer;
    let returnFocus;
    let previousOverflow;
    const mobile = window.matchMedia('(max-width: 820px)');
    function toast(message, error = false) {
        const target = $('.pcp-toast');
        clearTimeout(toastTimer);
        target.textContent = message;
        target.classList.toggle('is-error', error);
        target.hidden = false;
        toastTimer = setTimeout(() => { target.hidden = true; }, error ? 7000 : 2800);
    }
    function element(tag, className, text) {
        const el = document.createElement(tag);
        if (className) el.className = className;
        if (text !== undefined) el.textContent = text;
        return el;
    }
    function setBusy(value) {
        busy = value;
        app.classList.toggle('is-busy', value);
        $('[data-cart-items]').setAttribute('aria-busy', String(value));
        $('[data-checkout]').setAttribute('aria-disabled', String(value || !state?.count || failed));
    }
    async function request(operation = 'get', data = {}) {
        if (busy) return false;
        setBusy(true);
        try {
            const response = await fetch(perryPOS.endpoint, {
                method: 'POST', credentials: 'same-origin', cache: 'no-store',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: new URLSearchParams({operation, nonce: perryPOS.nonce, ...data}),
                signal: AbortSignal.timeout(30000),
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.data?.message || 'Your order could not be updated. Please try again.');
            failed = false;
            state = payload.data;
            renderCart();
            return true;
        } catch (error) {
            failed = true;
            $('[data-checkout]').classList.add('is-disabled');
            toast(error.name === 'TimeoutError' ? 'The connection timed out. Refresh the page to check your order.' : (error instanceof SyntaxError ? 'Could not connect to the store. Please refresh and try again.' : error.message), true);
            return false;
        } finally {
            setBusy(false);
        }
    }
    function renderCart() {
        $$('[data-cart-count]').forEach(el => { el.textContent = state.count; });
        $('[data-subtotal]').innerHTML = state.subtotal;
        $('[data-total]').innerHTML = state.total;
        $('[data-mobile-total]').innerHTML = state.total;
        $('[data-discount-row]').hidden = !state.discount;
        $('[data-discount]').innerHTML = state.discount ? '−' + state.discount : '';
        const checkout = $('[data-checkout]');
        checkout.classList.toggle('is-disabled', !state.count);
        checkout.href = state.checkout;
        checkout.setAttribute('aria-disabled', String(!state.count));
        const quantities = new Map();
        state.items.forEach(item => quantities.set(item.id, (quantities.get(item.id) || 0) + item.quantity));
        $$('[data-in-cart]').forEach(el => {
            const quantity = quantities.get(Number(el.dataset.inCart)) || 0;
            el.textContent = `${quantity} in order`;
            el.hidden = quantity === 0;
        });
        const container = $('[data-cart-items]');
        if (!state.items.length) {
            container.innerHTML = empty;
            return;
        }
        container.replaceChildren(...state.items.map(item => {
            const row = element('div', 'pcp-cart-item');
            const image = element('img', 'pcp-cart-item-image');
            image.src = item.image;
            image.alt = '';
            const body = element('div', 'pcp-cart-item-body');
            const title = element('h3', '', item.name);
            const controls = element('div', 'pcp-cart-item-controls');
            const quantity = element('div', 'pcp-quantity');
            const minus = element('button', '', '−');
            minus.type = 'button';
            minus.setAttribute('aria-label', `Decrease ${item.name} quantity`);
            minus.dataset.quantity = item.quantity - 1;
            minus.dataset.key = item.key;
            const number = element('span', '', item.quantity);
            number.setAttribute('aria-label', `Quantity: ${item.quantity}`);
            const plus = element('button', '', '+');
            plus.type = 'button';
            plus.setAttribute('aria-label', `Increase ${item.name} quantity`);
            plus.dataset.quantity = item.quantity + 1;
            plus.dataset.key = item.key;
            plus.disabled = item.max > 0 && item.quantity >= item.max;
            quantity.append(minus, number, plus);
            const price = element('span', 'pcp-line-price');
            price.innerHTML = item.price;
            controls.append(quantity, price);
            body.append(title, controls);
            const remove = element('button', 'pcp-cart-remove');
            remove.type = 'button';
            remove.innerHTML = closeIcon;
            remove.dataset.remove = item.key;
            remove.setAttribute('aria-label', `Remove ${item.name} from order`);
            row.append(image, body, remove);
            return row;
        }));
    }
    function filter() {
        const search = $('#pcp-search').value.trim().toLocaleLowerCase();
        let count = 0;
        for (const card of products) {
            const matchesCategory = activeCategory === 'all' || card.dataset.categories.split(' ').includes(activeCategory);
            card.hidden = !(matchesCategory && card.dataset.name.toLocaleLowerCase().includes(search));
            if (!card.hidden) count++;
        }
        $('[data-result-count]').textContent = `${count} ${count === 1 ? 'item' : 'items'}`;
        $('[data-no-results]').hidden = count > 0;
    }
    function closeCart(restoreFocus = true) {
        if (!app.classList.contains('cart-open')) return;
        app.classList.remove('cart-open');
        $('.pcp-cart-overlay').hidden = true;
        $('#pcp-order').removeAttribute('role');
        $('#pcp-order').removeAttribute('aria-modal');
        document.body.style.overflow = previousOverflow || '';
        $$('.pcp-catalog, .pcp-header, .pcp-mobile-cart').forEach(el => { el.inert = false; });
        $$('[data-open-cart]').forEach(el => el.setAttribute('aria-expanded', 'false'));
        if (restoreFocus && returnFocus?.isConnected) returnFocus.focus();
    }
    function openCart(trigger) {
        if (!mobile.matches) {
            $('#pcp-order').focus();
            return;
        }
        returnFocus = trigger;
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        app.classList.add('cart-open');
        $('.pcp-cart-overlay').hidden = false;
        $('#pcp-order').setAttribute('role', 'dialog');
        $('#pcp-order').setAttribute('aria-modal', 'true');
        $$('[data-open-cart]').forEach(el => el.setAttribute('aria-expanded', 'true'));
        $$('.pcp-catalog, .pcp-header, .pcp-mobile-cart').forEach(el => { el.inert = true; });
        $('.pcp-close-cart').focus();
    }
    app.addEventListener('click', async (event) => {
        const add = event.target.closest('[data-add]');
        const quantity = event.target.closest('[data-quantity]');
        const remove = event.target.closest('[data-remove]');
        const category = event.target.closest('[data-category]');
        if (add) {
            if (busy || add.disabled) return;
            if (await request('add', {product_id: add.dataset.add})) {
                add.classList.add('is-added');
                add.innerHTML = '✓';
                toast('Added to your order');
                setTimeout(() => { add.classList.remove('is-added'); add.innerHTML = plusIcon; }, 1200);
            }
        } else if (quantity) {
            if (quantity.disabled || busy) return;
            const key = quantity.dataset.key;
            const label = quantity.getAttribute('aria-label');
            if (await request('quantity', {key, quantity: quantity.dataset.quantity})) {
                const next = $$('[data-key]').find(el => el.dataset.key === key && el.getAttribute('aria-label') === label);
                if (next && !next.disabled) next.focus();
                else $('#pcp-order').focus();
            }
        } else if (remove) {
            if (await request('remove', {key: remove.dataset.remove})) {
                toast('Item removed');
                $('#pcp-order').focus();
            }
        } else if (category) {
            activeCategory = category.dataset.category;
            $$('[data-category]').forEach(el => { const selected = el === category; el.classList.toggle('is-active', selected); el.setAttribute('aria-pressed', String(selected)); });
            filter();
        } else if (event.target.closest('[data-reset-filters]')) {
            $('#pcp-search').value = '';
            $('[data-category="all"]').click();
        } else if (event.target.closest('[data-open-cart]')) {
            openCart(event.target.closest('[data-open-cart]'));
        } else if (event.target.closest('[data-close-cart]')) {
            closeCart();
        } else if (event.target.closest('[data-browse-menu]')) {
            closeCart(false);
            $('#pcp-menu').scrollIntoView({behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'});
            $('#pcp-search').focus({preventScroll: true});
        } else if (event.target.closest('[data-checkout]') && (busy || failed || !state?.count)) {
            event.preventDefault();
            if (failed) toast('Please refresh the page to confirm your order before checkout.', true);
        }
    });
    $('#pcp-search').addEventListener('input', filter);
    $('#pcp-sort').addEventListener('change', (event) => {
        const mode = event.target.value;
        const sorted = [...products].sort((a, b) => mode === 'name' ? a.dataset.name.localeCompare(b.dataset.name) : (Number(a.dataset.price) - Number(b.dataset.price)) * (mode === 'price-high' ? -1 : 1));
        sorted.forEach(card => $('#pcp-products').appendChild(card));
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName) && !app.classList.contains('cart-open')) {
            event.preventDefault();
            $('#pcp-search').focus();
        }
        if (!app.classList.contains('cart-open')) return;
        if (event.key === 'Escape') { closeCart(); return; }
        if (event.key === 'Tab') {
            const focusable = [...$('#pcp-order').querySelectorAll('button:not(:disabled), a[href], [tabindex="0"]')].filter(el => el.getClientRects().length);
            const first = focusable[0]; const last = focusable.at(-1);
            if (event.shiftKey && (document.activeElement === first || document.activeElement === $('#pcp-order'))) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
    });
    mobile.addEventListener('change', () => { if (!mobile.matches) closeCart(false); });
    window.addEventListener('pageshow', event => { if (event.persisted) request(); });
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible' && !busy) request(); });
    request();
})();
