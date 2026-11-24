const assert = require('node:assert/strict');
const path = require('node:path');
const fs = require('node:fs');
const {execFileSync} = require('node:child_process');
const {chromium} = require(path.resolve(__dirname, '../../../customer-display-app/node_modules/playwright-core'));

(async () => {
    const root = path.resolve(__dirname, '../../..');
    // Read one existing order for this local QA session; no customer data is logged or written to fixtures.
    const code = String.raw`require 'vendor/autoload.php'; $app=require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $s=Modules\Storefront\Entities\StoreSetting::where('business_id',config('storefront.default_business_id'))->firstOrFail(); $o=Modules\Storefront\Entities\StoreOrder::where('business_id',$s->business_id)->firstOrFail(); echo json_encode(['url'=>route('storefront.track',$s),'code'=>$o->tracking_code,'phone'=>$o->customer['phone']]);`;
    const fixture = JSON.parse(execFileSync('C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe', ['-r', code], {cwd: root, encoding: 'utf8'}));
    const out = path.join(root, 'storage/app/storefront-preview', process.env.STOREFRONT_PREVIEW_RUN || '.');
    fs.mkdirSync(out, {recursive: true});
    const browser = await chromium.launch({headless: true, executablePath: path.join(process.env.LOCALAPPDATA, 'ms-playwright/chromium-1194/chrome-win/chrome.exe')});
    try {
        const context = await browser.newContext({viewport: {width: 1440, height: 1050}});
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const response = await page.goto(fixture.url, {waitUntil: 'networkidle'});
        assert.equal(response.status(), 200);
        await page.screenshot({path: path.join(out, 'tracking-form.png')});
        await page.locator('[name="code"]').fill(fixture.code);
        await page.locator('[name="phone"]').fill(fixture.phone.replace(/\D/g, '') === '0000000000' ? '9999999999' : '0000000000');
        await page.locator('#sf-tracking-form button').click();
        await page.getByRole('alert').waitFor();
        assert.equal(await page.locator('#sf-tracking-result').count(), 0);
        await page.locator('[name="phone"]').fill(fixture.phone);
        await page.locator('#sf-tracking-form button').click();
        await page.locator('#sf-tracking-result').waitFor();
        await page.locator('[data-tracking-refresh-button]').click();
        await page.waitForFunction(() => document.querySelector('[data-tracking-refresh]').textContent.includes('Checked just now'));
        const endpoint = await page.locator('#sf-tracking-result').getAttribute('data-status-url');
        const state = await (await context.request.get(endpoint)).json();
        for (const field of ['customer', 'phone', 'email', 'address', 'items', 'token']) assert.equal(field in state, false);
        const other = await browser.newContext();
        assert.equal((await other.request.get(endpoint)).status(), 401);
        await other.close();
        await page.screenshot({path: path.join(out, 'tracking-progress.png'), fullPage: true});
        await page.setViewportSize({width: 390, height: 844});
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
        await page.screenshot({path: path.join(out, 'tracking-mobile.png'), fullPage: true});
        assert.deepEqual(errors, []);
        console.log(JSON.stringify({passed: true, checks: ['tracking form', 'wrong-phone rejection', 'code and phone lookup', 'manual status refresh', 'private status payload', 'unauthorized session rejection', 'mobile layout', 'browser errors'], screenshots: out}));
        await context.close();
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
