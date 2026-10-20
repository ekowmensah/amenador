# General Goods website

This folder is a complete static website. Upload its contents into your domain's document root. No PHP or build step is required.

Edit `assets/js/site-config.js` to set the business name, phone, email, address, opening hours and POS base URL. Leave unknown contact details blank; they stay hidden.

The live catalogue is optional and off by default. Enable it with `enableCatalogue: true` and point `catalogueUrl` at your POS public catalogue endpoint. The endpoint controls which business products are published.

When testing inside the project's main-domain-site folder on localhost, the staff portal is detected automatically. On hosting, the default POS base URL is `/shop/public`; change it to the actual POS installation URL.

Standalone copies do not read Laravel Business Settings automatically. Update this configuration file when public details change.
