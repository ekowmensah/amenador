/* Run from the POS checkout with its existing playwright-core development dependency. */
const assert = require('node:assert/strict');
const path = require('node:path');
const fs = require('node:fs');
const {chromium} = require(path.resolve(__dirname, '../../../../customer-display-app/node_modules/playwright-core'));

(async () => {
    const out = path.resolve(__dirname, '../../../../storage/app/customer-pos-preview');
    fs.mkdirSync(out, {recursive: true});
    const browser = await chromium.launch({headless: true, executablePath: path.join(process.env.LOCALAPPDATA, 'ms-playwright/chromium-1194/chrome-win/chrome.exe')});
    try {
        const context = await browser.newContext({viewport: {width: 1440, height: 1050}});
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const response = await page.goto('http://localhost/wordpress/order/', {waitUntil: 'networkidle'});
        assert.equal(response.status(), 200);
        await page.waitForFunction(() => document.querySelector('[data-checkout]')?.getAttribute('aria-disabled') === 'true' && !document.querySelector('.pcp.is-busy'));
        assert.ok(await page.locator('.pcp-product').count() > 0);
        assert.equal(await page.locator('[data-cart-count]').first().textContent(), '0');
        await page.screenshot({path: path.join(out, 'desktop-empty.png'), fullPage: false});

        await page.getByRole('button', {name: 'Wine', exact: true}).click();
        assert.ok(await page.locator('.pcp-product:visible').count() > 0);
        for (const text of await page.locator('.pcp-product:visible .pcp-product-category').allTextContents()) assert.equal(text, 'Wine');
        await page.locator('#pcp-search').fill('no-such-drink-938475');
        await page.locator('[data-no-results]').waitFor({state: 'visible'});
        assert.equal(await page.locator('.pcp-product:visible').count(), 0);
        await page.locator('[data-reset-filters]').click();
        await page.locator('#pcp-search').fill('Bacardi');
        assert.equal(await page.locator('.pcp-product:visible').count(), 1);
        await page.locator('.pcp-product:visible [data-add]').click();
        await page.waitForFunction(() => document.querySelector('[data-cart-count]').textContent === '1' && !document.querySelector('.pcp.is-busy'));
        assert.ok(await page.locator('[data-cart-items]').textContent().then(text => text.includes('BACARDI')));
        await page.getByRole('button', {name: 'Increase BACARDI BLACK 1L quantity'}).click();
        await page.waitForFunction(() => document.querySelector('[data-cart-count]').textContent === '2' && !document.querySelector('.pcp.is-busy'));
        await page.reload({waitUntil: 'networkidle'});
        await page.waitForFunction(() => document.querySelector('[data-cart-count]').textContent === '2' && !document.querySelector('.pcp.is-busy'));
        await page.screenshot({path: path.join(out, 'desktop-cart.png'), fullPage: false});

        const nonce = await page.evaluate(() => perryPOS.nonce);
        const endpoint = await page.evaluate(() => perryPOS.endpoint);
        const key = await page.locator('[data-remove]').getAttribute('data-remove');
        const rejected = await page.request.post(new URL(endpoint, page.url()).href, {form: {nonce, operation: 'quantity', key, quantity: '999999'}});
        assert.equal(rejected.status(), 400);
        const csrf = await page.request.post(new URL(endpoint, page.url()).href, {form: {nonce: 'invalid', operation: 'add', product_id: '19'}});
        assert.equal(csrf.status(), 403);

        await page.locator('[data-checkout]').click();
        await page.waitForURL('**/checkout/**');
        await page.waitForLoadState('networkidle');
        assert.ok((await page.locator('body').textContent()).includes('BACARDI'));
        await page.goto('http://localhost/wordpress/order/', {waitUntil: 'networkidle'});
        await page.waitForFunction(() => document.querySelector('[data-cart-count]').textContent === '2' && !document.querySelector('.pcp.is-busy'));

        await page.setViewportSize({width: 390, height: 844});
        await page.waitForFunction(() => document.querySelector('.pcp-order').getBoundingClientRect().left >= innerWidth);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth), false);
        await page.screenshot({path: path.join(out, 'mobile-menu.png'), fullPage: false});
        await page.locator('.pcp-mobile-cart').click();
        await page.locator('.pcp.cart-open').waitFor();
        await page.getByRole('dialog').waitFor();
        await page.waitForFunction(() => Math.abs(document.querySelector('.pcp-order').getBoundingClientRect().right - innerWidth) < 1);
        await page.screenshot({path: path.join(out, 'mobile-cart.png'), fullPage: false});
        await page.keyboard.press('Escape');
        assert.equal(await page.locator('.pcp.cart-open').count(), 0);
        await page.locator('.pcp-mobile-cart').click();
        await page.locator('[data-remove]').click();
        await page.waitForFunction(() => document.querySelector('[data-cart-count]').textContent === '0' && !document.querySelector('.pcp.is-busy'));
        assert.equal(await page.locator('[data-checkout]').getAttribute('aria-disabled'), 'true');
        assert.deepEqual(errors, []);
        console.log(JSON.stringify({passed: true, checks: ['catalog', 'category filters', 'search', 'empty results', 'add to cart', 'quantity', 'session persistence', 'stock validation', 'nonce validation', 'WooCommerce checkout handoff', 'mobile layout', 'mobile cart', 'keyboard dismissal', 'remove item', 'browser errors'], screenshots: out}));
        await context.close();
    } finally {
        await browser.close();
    }
})().catch(error => {console.error(error); process.exitCode = 1;});
