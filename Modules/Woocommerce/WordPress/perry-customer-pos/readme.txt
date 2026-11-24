=== Perry Customer POS ===
Requires at least: 6.6
Requires PHP: 8.0
Requires Plugins: woocommerce
Stable tag: 1.0.0
License: GPLv2 or later

A customer ordering page using the live WooCommerce catalog, stock, cart and checkout.

== Installation ==

Upload the perry-customer-pos folder (or its zip) in WordPress > Plugins, then activate it with WooCommerce active.
Activation creates an "Order online" page. It does not change the homepage, theme, product records or payment settings.
Open WooCommerce > Customer POS to find the page link and edit the store name, tagline and welcome headline.

== Features ==

* Responsive standalone page and [perry_customer_pos] shortcode.
* Category filters, product search and price/name sorting.
* Live WooCommerce product images, price, sales and stock availability.
* Server-side cart validation, quantity controls, removal and totals.
* Standard WooCommerce session: the same order is available at checkout and on reload.
* Variable and other product types link to the product page to choose options.
* Guest shopping; no administrator or API credentials are exposed to customers.
* Mobile cart drawer with keyboard focus management and a reduced-motion option.

== Checkout and POS integration ==

Checkout is the store's normal WooCommerce checkout, including its existing customer fields,
payment methods, tax and shipping rules. No payments or completed orders are created by this plugin's cart endpoint.
Orders created at checkout follow your existing WooCommerce-to-POS order sync or order webhooks.
This plugin does not replace or configure that integration. It reads the current WooCommerce catalog;
use the POS sync to publish POS changes to WooCommerce.

Amounts before checkout are estimates; final tax, shipping, coupons and payment options are determined at checkout.
Use WooCommerce's existing Cart or Checkout page to apply a coupon.
Alcohol age checks and store-specific checkout requirements should be handled by the store's existing checkout configuration.

== Page setup ==

Add [perry_customer_pos] to any WordPress page. Select "Customer POS — full screen" as
its page template for the standalone design, or leave the normal template to embed the store in your theme.
Use one instance per page. Catalog search and filters work on the published products visible in the catalog.
Keep this page and wc-ajax endpoints excluded from full-page caches, as with the standard WooCommerce cart.
The plugin sends no-cache headers and declares DONOTCACHEPAGE on the ordering page.

For a customer-facing homepage, select the ordering page under Settings > Reading.
Deactivation retains the created page and settings. Reactivation reuses its page instead of creating duplicates.
No external services, fonts, analytics or front-end build tools are required.
