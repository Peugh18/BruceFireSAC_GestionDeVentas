<?php

namespace App\Services\Billing\Data;

/**
 * Line item for a factura/boleta, already expressed in SUNAT terms (net of
 * discounts). Neutral data structure: nothing here depends on Greenter.
 */
final class SaleDocumentItemData
{
    public function __construct(
        public readonly string $unidad,
        public readonly float $cantidad,
        public readonly string $codProducto,
        public readonly string $descripcion,
        public readonly float $mtoValorUnitario,
        public readonly float $mtoValorVenta,
        public readonly float $mtoPrecioUnitario,
        public readonly float $mtoBaseIgv,
        public readonly float $porcentajeIgv,
        public readonly float $igv,
        public readonly string $tipAfeIgv,
    ) {}
}
