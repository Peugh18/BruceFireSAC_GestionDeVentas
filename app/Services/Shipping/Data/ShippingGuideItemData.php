<?php

namespace App\Services\Shipping\Data;

/**
 * A single good (bien) being transported, already expressed in SUNAT terms.
 * Neutral data structure: nothing here depends on Greenter.
 */
final class ShippingGuideItemData
{
    public function __construct(
        public readonly string $codigo,
        public readonly string $descripcion,
        public readonly string $unidad,
        public readonly float $cantidad,
    ) {}
}
