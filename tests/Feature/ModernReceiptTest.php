<?php

namespace Tests\Feature;

use Tests\TestCase;

class ModernReceiptTest extends TestCase
{
    private function receipt(): object
    {
        return (object) [
            'display_name' => 'Sample Store', 'invoice_no' => 'SALE-1042',
            'invoice_heading' => 'Sales receipt', 'invoice_date' => '20/10/2026 14:30',
            'customer_name' => 'Walk-In Customer', 'customer_label' => 'Customer',
            'table' => 'Table 4', 'service_staff' => 'Server A',
            'lines' => [[
                'name' => 'Item <special>', 'quantity' => '2', 'units' => 'pcs',
                'unit_price_before_discount' => 'GHS 10.00', 'unit_price_inc_tax' => 'GHS 10.00',
                'line_total' => 'GHS 20.00', 'sell_line_note' => 'No ice',
                'lot_number' => 'LOT-12', 'product_expiry' => '01/12/2027',
                'modifiers' => [['name' => 'Extra garnish', 'quantity' => '1', 'line_total' => 'GHS 2.00']],
            ]],
            'subtotal' => 'GHS 22.00', 'discount' => 'GHS 2.00', 'discount_label' => 'Discount',
            'tax' => 'GHS 1.00', 'tax_label' => 'Tax',
            'round_off_amount' => -0.01, 'round_off' => 'GHS -0.01',
            'total' => 'GHS 20.99', 'total_label' => 'Total',
            'total_paid' => 'GHS 20.99', 'total_paid_label' => 'Paid',
            'total_due' => 0, 'total_due_label' => 'Balance due',
            'payments' => [
                ['method' => 'Cash', 'amount' => 'GHS 30.00'],
                ['method' => 'Cash (Change)(-)', 'amount' => 'GHS 9.01'],
            ],
            'show_barcode' => false, 'show_qr_code' => false,
            'footer_text' => '<p>See you again.</p>',
        ];
    }

    public function test_modern_receipt_preserves_precalculated_amounts_and_optional_details(): void
    {
        $html = view('sale_pos.receipts.modern', ['receipt_details' => $this->receipt()])->render();
        foreach (['SALE-1042', 'GHS 20.99', 'GHS 9.01', 'GHS -0.01', 'Table 4', 'Server A', 'LOT-12', '01/12/2027', 'No ice', 'Extra garnish', 'See you again.'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringContainsString('Item &lt;special&gt;', $html);
        $this->assertStringContainsString('modern-document--receipt', $html);
        $this->assertStringContainsString('Balance due', $html);
    }

    public function test_invoice_uses_shared_content_with_a_unit_price_column(): void
    {
        $html = view('sale_pos.receipts.modern_invoice', ['receipt_details' => $this->receipt()])->render();
        $this->assertStringContainsString('modern-document--invoice', $html);
        $this->assertStringContainsString('<th class="modern-unit">Unit price</th>', $html);
        $this->assertStringContainsString('GHS 20.99', $html);
    }

    public function test_price_hidden_documents_do_not_leak_payment_or_price_amounts(): void
    {
        $receipt = $this->receipt();
        $receipt->hide_price = true;
        foreach (['modern', 'modern_invoice'] as $design) {
            $html = view('sale_pos.receipts.'.$design, ['receipt_details' => $receipt])->render();
            $this->assertStringNotContainsString('GHS', $html);
            $this->assertStringContainsString('Item &lt;special&gt;', $html);
            $this->assertStringContainsString('Extra garnish', $html);
        }
    }

    public function test_minimal_quotation_and_credit_note_render_without_payments(): void
    {
        foreach (['Quotation', 'Credit note'] as $heading) {
            $receipt = (object) ['invoice_heading' => $heading, 'invoice_no' => 'DOC-1', 'lines' => [], 'total' => '0.00'];
            $html = view('sale_pos.receipts.modern', ['receipt_details' => $receipt])->render();
            $this->assertStringContainsString($heading, $html);
            $this->assertStringContainsString('DOC-1', $html);
        }
    }

    public function test_both_designs_are_available_in_invoice_settings(): void
    {
        $controller = $this->app->make(\App\Http\Controllers\InvoiceLayoutController::class);
        $method = new \ReflectionMethod($controller, 'getDesigns');
        $method->setAccessible(true);
        $designs = $method->invoke($controller);
        $this->assertArrayHasKey('modern', $designs);
        $this->assertArrayHasKey('modern_invoice', $designs);
        $this->assertArrayHasKey('classic', $designs);
    }

    public function test_units_are_plural_only_above_one_for_items_and_modifiers(): void
    {
        foreach (['modern', 'modern_invoice'] as $design) {
            $receipt = $this->receipt();
            $receipt->lines[0]['units'] = 'btl';
            $receipt->lines[0]['quantity'] = '2,00';
            $receipt->lines[0]['quantity_uf'] = 2;
            $receipt->lines[0]['modifiers'][0]['units'] = 'box';
            $receipt->lines[0]['modifiers'][0]['quantity'] = '3';
            $receipt->lines[0]['modifiers'][0]['quantity_uf'] = 3;
            $html = view('sale_pos.receipts.'.$design, ['receipt_details' => $receipt])->render();
            $this->assertStringContainsString('2,00 <small>btls</small>', $html);
            $this->assertStringContainsString('3 <small>boxes</small>', $html);
        }
        foreach ([0, 0.5, 1] as $quantity) {
            $this->assertSame('btl', \App\Utils\ReceiptUnitUtil::label('btl', $quantity));
        }
        $this->assertSame('pcs', \App\Utils\ReceiptUnitUtil::label('pcs', 2));
        $this->assertSame('kg', \App\Utils\ReceiptUnitUtil::label('kg', 2));
    }

    public function test_barcodes_and_invoice_pdf_generation(): void
    {
        $receipt = $this->receipt();
        $receipt->show_barcode = true;
        $receipt->show_qr_code = true;
        $receipt->qr_code_text = 'SALE-1042';
        $html = view('sale_pos.receipts.modern_invoice', ['receipt_details' => $receipt])->render();
        $this->assertStringContainsString('alt="Invoice barcode"', $html);
        $this->assertStringContainsString('alt="Invoice QR code"', $html);
        $mpdf = new \Mpdf\Mpdf(['tempDir' => sys_get_temp_dir(), 'format' => 'A4', 'mode' => 'utf-8']);
        $mpdf->WriteHTML($html);
        $this->assertStringStartsWith('%PDF-', $mpdf->Output('', 'S'));
    }
}
