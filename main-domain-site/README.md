# Prak & Daughters main-domain website

This folder is a standalone static website for `https://prakanddaughters.com`.
It does not require Laravel, PHP, or database access.

## Deploy with cPanel File Manager

1. Open the main domain document root, usually `public_html`.
2. Back up any existing website files.
3. Upload the contents of this folder—not the enclosing folder—into `public_html`.
4. Confirm the staff portal opens at `/shop/public/login`.
5. Enable HTTPS in cPanel and test the site on desktop and mobile.

## Live catalogue dependency

The website loads products from `/shop/public/catalogue/products`. Upload these
Laravel files to the POS installation before testing the catalogue:

- `app/Http/Controllers/PublicCatalogueController.php`
- `routes/web.php`
- `config/constants.php`

The endpoint defaults to business ID `1`. If the live business uses another ID,
add `PUBLIC_CATALOG_BUSINESS_ID=YOUR_ID` to the POS `.env` file.

Public branch information was taken from the local Prak & Daughters database on 15 August 2026. Update `index.html` when branch details change.
