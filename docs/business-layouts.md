# Business layouts

Choose **General Goods / Mixed Business** under **Business Settings → POS → POS
layout** when one business sells across several departments. The same catalogue
can contain medicines, groceries, food, drinks, hardware and other products.
Categories, units, variants, stock tracking and existing permissions continue to
control how individual products work. A layout does not filter or restrict them.

Other choices are Hardware & Building Supplies, Bar & Restaurant, Pharmacy and
Supermarket. Layouts change presentation, search prompts and customer-facing copy.
They do not enable, disable or enforce specialist stock or restaurant features.
Configure those separately using the existing business/module settings.

The customer display follows the POS selection. The public and login pages can
follow it or use a separate selection under **Business Settings → Public & login
layout**. Guest pages use the business identified by `PUBLIC_CATALOG_BUSINESS_ID`.
Save settings and reopen POS to pick up a change.

Modern receipt and invoice designs work with every layout. They retain the
business's labels, units, lot/expiry fields, product notes, taxes and payments when
configured; no receipt is tied to a particular industry.

The standalone `main-domain-site` collection has matching versions, including
`general-goods/` and `hardware/`. Those copies have their own public configuration
and optional catalogue endpoint. Product selection comes from the configured
backend business, not from the website theme.

Existing saved selections and the previous fallback layout are preserved. Choose
the neutral General Goods option explicitly when setting up a mixed business.
