# Product syncing

Sync All now fetches newer WordPress edits for each page of linked products before
sending POS changes. Modification times decide which side wins: a newer local edit
is preserved for outgoing sync. Sync New still only sends new POS products.
WordPress changes can also be received for **already linked products** through a
Product updated webhook. This does not import new WordPress products, variant
changes, gallery images or stock quantities. Stock continues through the existing
order/stock flow.

## Setup

1. Open WooCommerce > API Settings in POS. In the outgoing create/update field
   lists, select **Image**. Existing saved selections are preserved; new settings
   include Image by default.
2. In Webhook Settings, set a strong Product updated secret and choose which
   fields to receive. Save the settings and copy the displayed delivery URL.
3. In WordPress, open WooCommerce > Settings > Advanced > Webhooks. Add an Active
   webhook with topic **Product updated**, the copied URL and the exact same secret.
4. Edit a linked product in WordPress and save it. Check its POS image/details and
   the `product_import` sync log. A delivery failure returns HTTP 500 and is logged
   in the application log; signature failures return HTTP 401.

The webhook fetches the current product through the configured WooCommerce API,
so delayed deliveries do not apply old payloads. It changes only selected fields,
does not send a product update back immediately, and avoids saving unchanged data.
The latest processed WordPress state wins for those fields; simultaneous edits on
both systems are not merged. Products with sync disabled are ignored. Categories
must already be mapped. Prices use the regular price (not a temporary sale price),
configured tax basis, and configured selling price group. Variant prices are not
imported.

## Images

The primary WordPress image is downloaded into POS product image storage. JPEG,
PNG, GIF and WebP are supported, up to 10 MB. Removing the primary image in either
system clears the linked image; outgoing sync uses the POS default image when no
photo is assigned, matching the picture displayed in POS. WooCommerce placeholder
images with media ID zero are not downloaded as product photos. Images are validated
from their contents; downloads must stay on the configured store host and port,
without redirects. CDN-hosted images need a separate allowlist implementation.
Old local files are retained because they may be shared by other records.

For POS-to-WordPress image uploads, the POS application URL must be publicly
reachable by WordPress: WooCommerce downloads the supplied image URL. A Laragon
localhost/private development URL cannot be fetched by a remote WordPress server.
The configured application URL must also point at the Laravel public document
root (for this local setup, `http://perryposs.test`), so `/img/` and `/uploads/`
URLs return images rather than 404s. Products missing a WooCommerce media ID are
retried even when their other details have not changed. WooCommerce batch errors
are surfaced to the Sync button instead of being reported as success.
Replacing a POS product image clears its cached WooCommerce media ID in the
existing product editor, allowing WooCommerce to fetch the replacement.

## Verification

Run `php vendor/bin/phpunit tests/Feature/WoocommerceProductSyncTest.php`.
The tests use a separate in-memory SQLite connection and fake HTTP responses;
they do not contact WordPress or change the application database.
