const assert = require('node:assert/strict');
const path = require('node:path');
const fs = require('node:fs');
const {execFileSync} = require('node:child_process');
const {chromium} = require(path.resolve(__dirname, '../../../customer-display-app/node_modules/playwright-core'));

(async () => {
    const root = path.resolve(__dirname, '../../..');
    // Render preview fixtures without enabling or changing a real store.
    const php = String.raw`require 'vendor/autoload.php'; $app=require 'bootstrap/app.php'; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); app('request')->setLaravelSession(app('session.store')); $app->register(Modules\Storefront\Providers\StorefrontServiceProvider::class); if(!Illuminate\Support\Facades\Route::has('storefront.asset')){require 'Modules/Storefront/Routes/web.php'; app('router')->getRoutes()->refreshNameLookups();} app()->instance(Modules\Storefront\Services\PublicStoreLink::class,new class extends Modules\Storefront\Services\PublicStoreLink {public function data():array{return ['publicStoreUrl'=>url('/shop/example-shop'),'publicStoreDelivery'=>true];}}); $middleware=new Modules\Storefront\Http\Middleware\HostIntegration(); $pages=[]; foreach(config('public_page_layouts') as $layout=>$preset){$pages[$layout]=$middleware->handle(Illuminate\Http\Request::create('/'),fn()=>response()->view('welcome',['pageLayout'=>$layout,'pagePreset'=>$preset,'publicBusinessName'=>'Example Business']))->getContent();} echo json_encode($pages);`;
    const fixtures = JSON.parse(execFileSync('C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe', ['-r', php], {cwd: root, encoding: 'utf8'}));
    const out = path.join(root, 'storage/app/storefront-preview/public-shop-link');
    fs.mkdirSync(out, {recursive: true});
    const browser = await chromium.launch({headless: true, executablePath: path.join(process.env.LOCALAPPDATA, 'ms-playwright/chromium-1194/chrome-win/chrome.exe')});
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
        // Serve local assets for intercepted previews too, avoiding loopback address-space restrictions.
        for (const directory of ['css', 'img']) {
            await page.route('http://perryposs.test/' + directory + '/**', route => {
                const name = path.basename(new URL(route.request().url()).pathname);
                return route.fulfill({path: path.join(root, 'public', directory, name)});
            });
        }
        await page.route('http://perryposs.test/storefront-assets/**', route => route.fulfill({path: path.join(root, 'Modules/Storefront/Resources/assets', path.basename(new URL(route.request().url()).pathname))}));
        for (const [layout, html] of Object.entries(fixtures)) {
            const preview = 'http://perryposs.test/__homepage_preview/' + layout;
            await page.route(preview, route => route.fulfill({status: 200, contentType: 'text/html', body: html}));
            for (const [device, width] of [['desktop', 1440], ['mobile', 390]]) {
                await page.setViewportSize({width, height: 960});
                await page.goto(preview, {waitUntil: 'networkidle'});
                assert.equal(await page.locator('body').evaluate(el => getComputedStyle(el).fontFamily.includes('Arial')), true, await page.evaluate(() => location.href + ' origin=' + location.origin + ' font=' + getComputedStyle(document.body).fontFamily));
                assert.equal(await page.locator('.public-shop-nav').isVisible(), true, layout + ' ' + device);
                assert.equal(await page.locator('.public-shop-hero').isVisible(), true);
                assert.equal(await page.locator('.public-shop-hero').getAttribute('href'), 'http://perryposs.test/shop/example-shop');
                assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false, layout + ' overflow on ' + device);
                await page.screenshot({path: path.join(out, layout + '-' + device + '.png')});
            }
        }
        assert.deepEqual(errors, []);
        console.log(JSON.stringify({passed: true, layouts: Object.keys(fixtures), checks: ['desktop and mobile buttons', 'matching store URL', 'no horizontal overflow', 'browser errors'], screenshots: out}));
    } finally { await browser.close(); }
})().catch(error => {console.error(error); process.exitCode = 1;});
