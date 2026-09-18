<?php

namespace App\Services\Shipping\Data;

use DateTimeImmutable;

/**
 * Neutral representation of a GRE (guia de remision) built from a
 * ShippingGuide. ShippingGuideBuilder produces this; only GreenterService
 * turns it into Greenter's own Despatch model classes.
 */
final class ShippingGuideData
{
    /**
     * @param  list<ShippingGuideItemData>  $items
     */
    public function __construct(
        public readonly string $serie,
        public readonly string $correlativo,
        public readonly DateTimeImmutable $fechaEmision,
        public readonly DateTimeImmutable $fechaInicio,
        public readonly string $motivoSunatCode,
        public readonly string $motivoDescripcion,
        public readonly string $origen,
        public readonly string $destino,
        public readonly string $destinatarioTipoDoc,
        public readonly string $destinatarioNumDoc,
        public readonly string $destinatarioNombre,
        public readonly string $modalidad,
        public readonly float $pesoTotal,
        public readonly array $items,
        public readonly ?string $transportistaRazonSocial = null,
        public readonly ?string $transportistaRuc = null,
        public readonly ?string $vehiculoPlaca = null,
        public readonly ?string $conductorNombre = null,
        public readonly ?string $conductorLicencia = null,
        public readonly ?string $observacion = null,
    ) {}
}
