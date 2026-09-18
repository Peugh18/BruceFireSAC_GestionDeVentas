<?php

namespace App\Services\Billing;

/**
 * Builds the neutral SaleDocumentData for a factura (tipoDoc 01), issued
 * when the client's tipo_documento is 'ruc'.
 */
class InvoiceBuilder extends SaleDocumentBuilder
{
    public function tipoDoc(): string
    {
        return '01';
    }
}
