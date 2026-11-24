# Storefront module 1.3.1

A standalone customer shop and checkout inside the POS. It reads POS products,
variations, product images, default selling prices and location stock directly.
It has no WordPress or WooCommerce dependency and makes no sync requests.

The shop, checkout, receipts and tracking pages follow the business's existing
Public/Login Layout setting, including Follow POS. All five presets share the
public/login colors, typography and illustrations. The storefront resolves its
own business settings independently of staff sessions. Compatible hosts use the
shared public presets and assets; bundled presets and artwork support installs
without the host's public-layout customization.

## Sales and customer tracking

Storefront sales display their tracking code, pickup/delivery type, order progress
and pay-on-collection label under the invoice number. Clicking the code opens
the existing sale details. Use the normal sales shipping editor: Ordered maps to
Received, Packed to Ready, Shipped to Out for delivery (Ready for pickup), and
Delivered to Completed. Storefront progress changes also update the sale;
Preparing maps to Ordered while retaining Preparing on customer tracking.

Record cash collected through the sale's normal Add Payment action. Partial
payments leave an outstanding payment; full payment marks the customer order
Paid. Editing shipping never collects payment. Cancelled shipping displays an
order cancellation and does not refund payments or reverse stock automatically.
Use the existing POS returns and refund workflows for those operations.

The module's model listeners provide shipping/payment synchronization on
compatible hosts without editing their controllers. The sales invoice badges
use the `sell_list_invoice_badges` ModuleUtil hook added to this repository's
SellController invoice renderer, with `transactions.source` included in its
query. On another host, add those two small integration points from this
repository to show the badges; synchronization still works without that hook.

## Requirements

- An UltimatePOS-compatible host with Laravel 9, PHP 8+, the existing `App` POS
  models/utilities, and `nwidart/laravel-modules` 9.
- MySQL/InnoDB for production row locking and transactions.
- An active business location with products assigned to it and correctly mapped
  opening/purchase stock. Single and variable products are supported. Combos,
  modifiers, bundles, custom price groups and loyalty-account redemption are not
  part of guest checkout in this release.
- Quantities are whole units, with up to 99 units per variant and 50 cart lines.
- The host's normal `Modules\` autoload mapping, session/CSRF middleware and HTTPS
  for public deployment. No new Composer or Node dependencies are required at runtime.

This is a module for compatible POS hosts, not a generic Laravel or WordPress plugin.
The host's models and accounting utilities are required.

## Install on another system

1. Extract the ZIP so its `Storefront` directory is at `Modules/Storefront`.
   Uploading through older Module Management pages only extracts the ZIP. If
   clicking Install does nothing, the module is still disabled or routes are
   cached. In cPanel Terminal, open the POS project root and run
   `php artisan module:enable Storefront` and `php artisan route:clear`.
   Refresh Module Management and click Install again. Without Terminal, add
   `"Storefront": true` to the existing root `modules_statuses.json` object,
   preserving every other entry. Cached routes still require `route:clear`.
2. From the POS project root, run:

   ```text
   php artisan module:enable Storefront
   php artisan storefront:install --business=1
   php artisan route:clear
   php artisan config:clear
   ```

   Use the correct business ID. The install command runs only this module's
   migrations and is safe to repeat; it retains existing settings and orders.
   If the host's autoloader requires regeneration, run `composer dump-autoload`.
3. Log in as that business's administrator and open `/storefront/settings`.
   The module also adds **Storefront** to the administrator sidebar.
4. Select the stock location, set the store URL/name, choose payment methods,
   and enable customer ordering. Grant `storefront.manage` to additional staff
   through the host's permissions UI if needed.
5. Share the store's `/shop/{store-name}` URL. `/shop` redirects to the business
   selected by `STOREFRONT_BUSINESS_ID` (default: `1`). The host homepage is retained.

The host Module Management install/update links also open the module's own
installation screen. This distribution does not use a third-party license server.
Module Management can disable the module. Disable new ordering in settings first;
financial records are retained. Do not roll back the schema to deactivate a live shop.

Clicking Uninstall opens a confirmation page. Confirming disables customer ordering
for every business, deactivates the module, removes its installed-version marker,
and clears cached routes. Order/settings tables, payments and POS sales remain.
Re-enable and install again to restore access; enable ordering explicitly in settings.
Complete pending online payments before uninstalling because payment callbacks
are unavailable while the module is disabled.

## Delivery and currency

Currency code, symbol and separators come from the business's currency settings.
Checkout stores immutable amounts in integer hundredths and displays two decimals.
This release therefore targets currencies priced in hundredths, including GHS.
Online Paystack is offered only for GHS, NGN, ZAR, KES and USD, subject to merchant
account eligibility. Guest offline checkout still uses the business's currency.

Pickup is free. To offer delivery, enter a flat delivery fee in that same currency,
then enable delivery. A fee of `0` explicitly enables free delivery; an unset fee
keeps delivery unavailable. Customers must supply a street address and city.
The delivery-area description is informational; distance pricing, carrier booking,
delivery scheduling and geographic zone validation are not included.

## Paystack

Use each installation's server-side `.env` configuration:

```dotenv
PAYSTACK_SECRET_KEY=sk_test_your_key
# Optional modern webhook signing secret, when enabled in Paystack:
PAYSTACK_WEBHOOK_SECRET=whsec_your_signing_secret
# Optional default store used by /shop:
STOREFRONT_BUSINESS_ID=1
```

Run `php artisan config:clear` after editing configuration. Hosted payment
initialization needs the secret key; it does not expose or require a public key
in the browser. Existing `PAYSTACK_PUBLIC_KEY` and `PAYSTACK_PAYMENT_URL` settings
can remain for the host's other integrations. This module calls Paystack's official
HTTPS API directly and redirects only to `checkout.paystack.com`.

Set this Paystack webhook URL, using the deployment's actual public HTTPS domain:

```text
https://your-pos-domain/storefront/paystack/webhook
```

The callback is `/storefront/paystack/callback`. The browser returning from Paystack
does not prove payment: the module verifies the provider's reference, successful
status, amount, currency, customer email and test/live mode before recording payment.
It validates raw-body SHA512 webhook signatures, including Paystack's timestamped
signing-secret format and its legacy secret-key format. When a modern secret is
configured, legacy signature fallback is disabled. Only the webhook is CSRF-exempt.

A localhost URL cannot receive Paystack's server-to-server webhooks. Local callbacks
can be tested in the same browser; use a public HTTPS deployment or tunnel for full
gateway testing. No real payment keys or live charges are included in the module ZIP.

All stores in one installation currently use that installation's Paystack merchant
credentials. Per-business merchant credentials and additional online gateways need
separate adapters; adding Stripe/PayPal keys to `.env` alone does not enable them.

## Order tracking

Each new order has a short random code such as `SF-7K3M-2Q8X`, shown with a Copy
code button on the confirmation page. The upgrade assigns codes to existing orders
without changing their original references, receipts, payments or progress.

Customers can open **Track order** from the shop, or visit `/shop/{store-name}/track`.
They enter the code and the full phone number used at checkout. Spaces, punctuation
and lowercase codes are accepted. The full original `SF-...` order reference also
works. Phone matching ignores formatting but requires the complete number, including
the country code if originally supplied; it does not authorize by the last few digits.

Lookups are limited to 10 attempts per minute and scoped to the selected business.
Failed lookups use a generic message. Successful access is stored in the browser
session for one hour; codes and phone numbers are not put into tracking URLs.
The tracking screen refreshes every 30 seconds and also has a manual refresh button.
Its status endpoint requires that authorized session and returns no names, phone
numbers, email addresses, delivery addresses, item lists or private receipt tokens.

Pickup progress is Received → Preparing → Ready for pickup → Collected. Delivery
adds Ready for dispatch and Out for delivery before Delivered. These are staff-set
updates from Customer orders, not live driver GPS. Pending payment and paid-review
orders show their payment state without falsely claiming preparation has started.

Existing installations update by replacing the module files and running
`php artisan storefront:install --business=1`; this applies the tracking migration.

## Sales, stock and order management

- Prices and totals are calculated server-side. A changed checkout quote requires
  the customer to review the new amount before ordering.
- Checkout is tied to the guest session and a unique checkout key. Retries reuse the
  existing order rather than creating duplicates. Cart changes start a new checkout.
- Pay-on-pickup/delivery orders immediately create final **unpaid** direct sales in
  the POS, including stock deduction and FIFO/LIFO purchase-line mapping. Collect
  payment through the normal POS payment screen. The module's payment-status hook
  updates the customer order when the POS records full payment.
- Online orders create a pending storefront record first. Stock is not reserved
  while a customer is away paying. Verified payment creates one paid POS sale and
  one card payment line, with the Paystack reference in its note/transaction number.
- Stock is rechecked under a database lock at acceptance. If stock, currency or POS
  accounting prevents creation after payment, verified funds remain recorded in a
  **Paid review** order. Staff see it under Storefront > Customer orders and can retry
  POS sale creation after resolving the issue, or use their normal refund workflow.
  The customer is explicitly told not to pay again. Duplicate callbacks/webhooks do
  not duplicate stock deduction or payments.
- Customer order URLs contain an unguessable bearer token. They are private receipts;
  customers should retain their link and avoid sharing it publicly.
- Staff can update preparation/collection/delivery progress. This does not capture,
  refund or reverse payments. Use the host's void/return workflows for existing sales
  and the gateway's refund tools for online refunds.
- Guest details create a separate customer record per accepted order. Guest-supplied
  email/phone never modifies a pre-existing customer's account or credit balance.

## Validation and packaging

From the host project root:

```text
php vendor/bin/phpunit Modules/Storefront/Tests/StorefrontTest.php
php Modules/Storefront/Tests/pos-integration.php
node Modules/Storefront/Tests/browser.smoke.cjs
php Modules/Storefront/build.php
```

The PHPUnit suite uses an isolated in-memory SQLite database and fake gateway
responses. The POS integration script tests the real host's sale, stock, accounting
and payment utilities in a transaction and verifies rollback; it needs one product
with at least three units available and never calls a real gateway. Avoid running
development integration checks during live checkout traffic.

The browser smoke test uses this workspace's existing Playwright dependency and
Chromium installation. It tests the page and checkout without submitting an order.
Adapt its development host/browser paths if running elsewhere.

The build command produces `storage/app/module-packages/Storefront-1.3.1.zip` with
portable ZIP entry paths and no `.env`, database dumps, customer files or credentials.
