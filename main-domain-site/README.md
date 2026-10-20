# Main-domain website collection

Five self-contained static websites are available. General Goods / Mixed Business
is suitable for stores selling across several departments; the specialist designs
also work with any product catalogue.

| Business | Folder | Local preview |
| --- | --- | --- |
| General Goods / Mixed Business | `general-goods/` | `http://localhost/perryposs/main-domain-site/general-goods/` |
| Hardware & Building Supplies | `hardware/` | `http://localhost/perryposs/main-domain-site/hardware/` |
| Pharmacy | `pharmacy/` | `http://localhost/perryposs/main-domain-site/pharmacy/` |
| Supermarket | `supermarket/` | `http://localhost/perryposs/main-domain-site/supermarket/` |
| Bar & Restaurant | `bar-restaurant/` | `http://localhost/perryposs/main-domain-site/bar-restaurant/` |

Open `http://localhost/perryposs/main-domain-site/templates.html` to compare them.
The existing root `index.html` and its original assets have been preserved.

## Configure a version

Edit `assets/js/site-config.js` **inside the selected folder**:

- `businessName`, `phone`, `email`, `address`, `openingHours`: public business details. Empty contact fields remain hidden.
- `posBaseUrl`: the POS installation base URL, such as `https://example.com/shop/public`. The staff links add `/login` automatically.
- `enableCatalogue`: set to `true` to display live products, category filters, location filters, search and pagination.
- `catalogueUrl`: your POS catalogue URL, normally `<posBaseUrl>/catalogue/products`. Leave blank to derive it from the POS base URL.
- `currency` and `locale`: price formatting, defaulting to `GHS` and `en-GH`.

An empty POS base URL detects this project's local `/perryposs/public` installation when previewed from `main-domain-site` on localhost. On a hosted domain it defaults to `/shop/public`.

These pages run without Laravel or a build step. They do **not** automatically read Business Settings; their configuration is independent of the Laravel public/login layout selector.

## Live catalogue

The catalogue is optional and disabled by default. When enabled it uses the existing public endpoint in `PublicCatalogueController`. The backend's `PUBLIC_CATALOG_BUSINESS_ID` selects which business is published; selecting a website theme does not change that business or filter its products. Use the correct backend for each deployed business.

Keep the site and POS on the same origin, or configure allowed origins on the POS server. If the API is unavailable the page remains usable and shows a clear catalogue message. It does not display fabricated products or prices.

## Deploy one version

1. Back up the destination website.
2. Configure your chosen folder and test the staff link and optional catalogue.
3. Upload **that folder's contents**, including its `assets/`, `robots.txt` and `.htaccess`, into the domain document root (usually `public_html`). Upload only the selected version.
4. Enable HTTPS and test on desktop and mobile.
5. If publishing a sitemap, generate it with the actual domain. The new variants do not embed the old Prak domain in robots or sitemap files.

Local artwork and fonts require no third-party image or font services. The only optional external dependency is your configured POS catalogue.

## Rebuild the collection

From the project root run `php scripts/build-static-sites.php`. The builder uses
`config/public_page_layouts.php`, the public-page CSS/artwork, and
`main-domain-site/template-kit/` as its sources. It rebuilds the five folders and
gallery while preserving each existing `assets/js/site-config.js`. Edit the source
templates/configuration rather than generated HTML if you plan to rebuild later.
