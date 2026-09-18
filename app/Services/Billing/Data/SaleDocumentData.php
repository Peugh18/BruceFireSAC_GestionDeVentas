<?php

namespace App\Services\Billing\Data;

use DateTimeImmutable;

/**
 * Neutral representation of a factura/boleta built from a Sale. Builders
 * (InvoiceBuilder/ReceiptBuilder) produce this; only GreenterService turns it
 * into Greenter's own model classes.
 */
final class SaleDocumentData
{
    /**
     * @param  list<SaleDocumentItemData>  $items
     * @param  list<array{monto: float, fecha: DateTimeImmutable}>  $cuotas
     */
    public function __construct(
        public readonly string $tipoDoc,
        public readonly string $serie,
        public readonly string $correlativo,
        public readonly DateTimeImmutable $fechaEmision,
        public readonly string $clientTipoDoc,
        public readonly string $clientNumDoc,
        public readonly string $clientRznSocial,
        public readonly ?string $clientDireccion,
        public readonly string $condicionPago,
        public readonly float $mtoOperGravadas,
        public readonly float $mtoIGV,
        public readonly float $totalImpuestos,
        public readonly float $valorVenta,
        public readonly float $subTotal,
        public readonly float $mtoImpVenta,
        public readonly array $items,
        public readonly array $cuotas = [],
        public readonly ?string $observacion = null,
    ) {}
}
