const assert = require('node:assert/strict');
const path = require('node:path');
const fs = require('node:fs');
const {chromium} = require(path.resolve(__dirname, '../../../customer-display-app/node_modules/playwright-core'));

(async () => {
    const out = path.resolve(__dirname, '../../../storage/app/storefront-preview', process.env.STOREFRONT_PREVIEW_RUN || '.');
    fs.mkdirSync(out, {recursive: true});
    const browser = await chromium.launch({headless: true, executablePath: path.join(process.env.LOCALAPPDATA, 'ms-playwright/chromium-1194/chrome-win/chrome.exe')});
    try {
        const page = await browser.newPage({viewport: {width: 1440, height: 1050}});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const response = await page.goto('http://perryposs.test/shop', {waitUntil: 'networkidle'});
        assert.equal(response.status(), 200);
        const storeUrl = page.url();
        await page.waitForFunction(() => !document.querySelector('.pcp.is-busy'));
        assert.ok(await page.locator('.pcp-product').count() > 0);
        await page.screenshot({path: path.join(out, 'desktop-store.png')});
        const card = page.locator('.pcp-product').filter({has: page.locator('[data-add]:not(:disabled)')}).first();
        const name = await card.getAttribute('data-name');
        await page.locator('#pcp-search').fill(name);
        assert.ok(await page.locator('.pcp-product:visible').count() >= 1);
        await card.locator('[data-add]').click();
        await page.waitForFunction(() => document.querySelector('[data-cart-count]').textContent === '1' && !document.querySelector('.pcp.is-busy'));
        await page.reload({waitUntil: 'networkidle'});
        await page.waitForFunction(() => document.querySelector('[data-cart-count]').textContent === '1' && !document.querySelector('.pcp.is-busy'));
        await page.locator('[data-checkout]').click();
        await page.waitForURL('**/checkout');
        assert.equal(await page.locator('#sf-checkout-form').count(), 1);
        assert.equal(await page.locator('input[name="payment_method"][value="cash"]').count(), 1);
        assert.equal(await page.locator('input[name="payment_method"][value="paystack"]').count(), 0);
        await page.locator('[name="name"]').fill('Browser preview customer');
        await page.locator('[name="email"]').fill('preview@example.invalid');
        await page.locator('[name="phone"]').fill('0240000000');
        await page.screenshot({path: path.join(out, 'desktop-checkout.png')});
        // Do not submit a real order from the browser smoke test.
        await page.setViewportSize({width: 390, height: 844});
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
        await page.screenshot({path: path.join(out, 'mobile-checkout.png'), fullPage: true});
        await page.goto(storeUrl, {waitUntil: 'networkidle'});
        await page.waitForFunction(() => document.querySelector('[data-cart-count]').textContent === '1' && !document.querySelector('.pcp.is-busy'));
        await page.screenshot({path: path.join(out, 'mobile-store.png')});
        await page.locator('.pcp-mobile-cart').click();
        await page.waitForFunction(() => Math.abs(document.querySelector('.pcp-order').getBoundingClientRect().right - innerWidth) < 1);
        await page.screenshot({path: path.join(out, 'mobile-cart.png')});
        await page.keyboard.press('Escape');
        assert.equal(await page.locator('.pcp.cart-open').count(), 0);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
        assert.deepEqual(errors, []);
        console.log(JSON.stringify({passed: true, checks: ['direct POS catalog', 'search', 'cart', 'session persistence', 'native checkout', 'payment availability', 'mobile checkout', 'mobile cart', 'keyboard dismissal', 'browser errors'], screenshots: out}));
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
