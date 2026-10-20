# AdomGrace Legacy POS Customer Display

A Windows companion for the existing Laravel POS. The cashier keeps using the browser. The customer screen runs in a separate fullscreen desktop window. The original browser customer display remains available.

## Install and pair

1. Install `dist/AdomGrace-Legacy-POS-Customer-Display-Setup-1.0.2.exe` on the POS computer and open **AdomGrace Legacy POS Customer Display**.
2. Set Windows display settings to **Extend these displays** and connect the customer monitor.
3. In `/pos/create`, select **Display app → Generate pairing code**.
4. Enter the server address shown in that dialog, a display name, and the eight-digit code into the Windows app. Codes expire after five minutes and can be used once.
5. In the POS dialog, enable **Use the desktop app for automatic customer display**. Also enable **Business Settings → POS → Automatically open customer display** if automatic opening is wanted. The existing desktop icon can still open the browser display manually.
6. The app chooses a secondary monitor. In app settings, choose a specific monitor if there are several, and optionally enable starting with Windows. **Allow the primary monitor** is available for single-screen testing and is off by default.

Use **Show desktop display** / **Hide desktop display** in the POS dialog for manual control, including when business auto-open is disabled. The app must already be running, either manually or through its optional Windows startup setting. Closing the settings window keeps the paired app in the tray; use its tray menu to quit. Preview shows the customer screen for 20 seconds.

Keep one POS tab open per register browser profile. Separate registers should use separate browser profiles or computers. Pairing is scoped to the business, cashier, and this browser's register identity. Switching cashier requires pairing for that cashier; old paired devices can be removed by their original cashier. Clearing browser storage creates a new register identity.

## Online hosting

Deploy the Laravel changes and run the additive migration:

```sh
php artisan migrate --path=database/migrations/2026_10_20_120000_create_customer_display_devices_table.php --force
php artisan route:clear
```

Without terminal access, open cPanel's **phpMyAdmin**, select the existing POS database (the `DB_DATABASE` entry in the server's `.env`), and import `database/customer-display-companion-cpanel.sql`. It creates the companion table and records the migration as applied. Upload the updated project files too. If a cached route file exists, remove only `bootstrap/cache/routes-v7.php` through File Manager so Laravel loads the new routes.

The server address must use HTTPS, including any installation subdirectory (for example `https://example.com/pos/public`). HTTP is accepted only for localhost development. Do not enter `/pos/create` or `/login` as the server address.

No WebSocket service or incoming port on the POS computer is required. The cashier sends order changes immediately with an 80 ms coalescing delay, and sends an unchanged heartbeat approximately every four seconds. The app checks every 500?750 ms while the display is active, and less often while inactive. Active order changes should usually appear in about a second, depending on network and server response times; this is fast polling rather than a WebSocket connection. Failed requests retry with exponential backoff up to 15 seconds. A register heartbeat expires after 15 seconds, clears the customer order, and hides the window. Network errors clear the visible order immediately; after 15 seconds without a successful connection the window hides. Polling reconnects automatically. The last order is deliberately not retained on disk.

Keep the Laravel cache persistent between requests (`file`, `database`, or Redis); do not use the `array` driver in production. With multiple web servers, use a shared cache. Cache entries are short lived. Ensure the web server forwards the `Authorization` header to PHP, and allow the companion endpoints through the hosting firewall. Each device has its own polling rate limit so registers behind one public IP can operate together. Pairing attempts are separately rate limited.

The original browser display does not depend on the companion service. Its route and popup behavior remain available; the desktop preference suppresses only its automatic popup in that browser.

## Credentials and removal

The server stores a hash of each device token; Windows encrypts the app's token using Electron safeStorage. Tokens stay in the main process and are never passed to the display page, URL or browser storage. The remote display has no Node access, accepts no navigation or popup windows, and uses a narrow preload bridge for order messages only.

Use **Unpair** beside a device in the POS dialog to revoke its access immediately. **Forget register on this computer** removes the local credential; also unpair in the POS to revoke the server credential. If a cashier is deleted, its display loses access. The app does not grant access to sales history, customer records, or other registers.

## Build and validate

Requires Windows, Node.js 22+, and network access to download Electron/build tooling:

```sh
cd customer-display-app
npm ci
npm test
npm run test:desktop
npm start
npm run dist
```

The installer and unpacked app are generated in `dist/` and are excluded from Git. Distribution is manual; the build does not publish to GitHub or install itself. This initial installer is unsigned. Production code signing can be configured separately.

The desktop smoke test launches the real Electron app against a temporary local mock server, uses an isolated app profile, and does not change Windows startup preferences. It checks pairing, encrypted credentials, authenticated display loading, live orders, inactive-register hiding, and device revocation.

Backend tests use an isolated SQLite memory database:

```sh
php vendor/phpunit/phpunit/phpunit tests/Feature/CustomerDisplayCompanionTest.php
```

Verify the actual monitor selection on each POS machine after installation. Monitor selection and hot-plug handling are automatic; the selected monitor preference is retained locally. Restart the app after changing the business display layout to load the newly selected theme.
