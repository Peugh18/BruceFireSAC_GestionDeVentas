<?php

namespace App\Services\Billing\Data;

use DateTimeImmutable;

/**
 * Neutral representation of a nota de credito/debito built from a
 * CreditDebitNote. CreditDebitNoteBuilder produces this; only
 * GreenterService turns it into Greenter's own Note model class.
 */
final class CreditDebitNoteData
{
    public function __construct(
        public readonly string $tipoDoc,
        public readonly string $serie,
        public readonly string $correlativo,
        public readonly DateTimeImmutable $fechaEmision,
        public readonly string $codMotivo,
        public readonly string $desMotivo,
        public readonly string $tipDocAfectado,
        public readonly string $numDocAfectado,
        public readonly string $clientTipoDoc,
        public readonly string $clientNumDoc,
        public readonly string $clientRznSocial,
        public readonly float $mtoOperGravadas,
        public readonly float $mtoIGV,
        public readonly float $valorVenta,
        public readonly float $subTotal,
        public readonly float $mtoImpVenta,
        public readonly string $descripcion,
    ) {}
}
