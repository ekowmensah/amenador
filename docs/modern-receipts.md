# Modern receipts and invoices

In **Settings → Invoice Settings**, add or edit an invoice layout and choose:

- **Modern POS Receipt (80mm)** for a compact thermal receipt.
- **Modern Invoice (A4)** for a full-page invoice with a unit-price column.

Keep the existing layout's logo, labels, customer visibility, item information,
payment visibility, tax summary, footer, QR and barcode options as needed.
Assign it under **Business Locations → Edit → Invoice layout for POS** (and
**Invoice layout for sale** for direct sales). If
the POS invoice-layout selector is enabled, a cashier can choose a layout there.

Both templates use the receipt data prepared by `TransactionUtil`; they do not
recalculate discounts, taxes, payment totals or change. Restaurant modifiers,
table and server information, lot/expiry data and item notes display when
provided by the invoice layout. Quotations and credit notes retain their headings.

Checkout and browser reprints render the selected design. For thermal printing,
select 80mm paper in the browser/printer settings, use small margins and disable
browser headers and footers. The A4 design uses the selected printer paper size.
Long orders flow across pages without splitting individual rows where possible.

When PDF downloads or emailed PDF invoices are enabled, both modern designs
use the A4 version. Export invoices retain their existing export PDF workflow.
Direct ESC/POS printing still uses the existing printer formatter rather than
HTML designs. Other receipt designs and the active location layout are unchanged.
