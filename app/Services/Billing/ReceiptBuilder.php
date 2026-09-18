<?php

namespace App\Services\Billing;

/**
 * Builds the neutral SaleDocumentData for a boleta (tipoDoc 03), issued
 * when the client's tipo_documento is not 'ruc' (persona natural).
 *
 * Note: Greenter's own `Greenter\Model\Sale\Receipt` class is unrelated to
 * this (it models a "Recibo por Honorarios" for independent professionals).
 * Both factura and boleta map to Greenter's Invoice model, differing only
 * in tipoDoc/serie, which is why this extends InvoiceBuilder's shared logic.
 */
class ReceiptBuilder extends SaleDocumentBuilder
{
    public function tipoDoc(): string
    {
        return '03';
    }
}
