<?php

namespace App\Services\Shipping;

use App\Models\Client;
use App\Models\ShippingGuide;
use App\Services\Shipping\Data\ShippingGuideData;
use App\Services\Shipping\Data\ShippingGuideItemData;
use DateTimeImmutable;

/**
 * Turns a ShippingGuide (and its items) into the neutral ShippingGuideData
 * consumed by GreenterService::sendDespatch(). Nothing here depends on
 * Greenter.
 */
class ShippingGuideBuilder
{
    public function build(ShippingGuide $guide): ShippingGuideData
    {
        $guide->loadMissing(['items', 'destinatarioClient']);

        [$tipoDoc, $numDoc, $nombre] = $this->resolveDestinatario($guide);

        $items = $guide->items
            ->map(fn ($item) => new ShippingGuideItemData(
                codigo: $item->sale_item_id ? "SI-{$item->sale_item_id}" : "ITEM-{$item->id}",
                descripcion: $item->descripcion,
                unidad: $item->unidad ?: 'NIU',
                cantidad: (float) $item->cantidad,
            ))
            ->all();

        return new ShippingGuideData(
            serie: $guide->serie,
            correlativo: $guide->correlativo,
            fechaEmision: new DateTimeImmutable($guide->created_at?->toDateString() ?? now()->toDateString()),
            fechaInicio: new DateTimeImmutable($guide->fecha_inicio->toDateString()),
            motivoSunatCode: ShippingGuide::MOTIVO_SUNAT_CODES[$guide->motivo_traslado],
            motivoDescripcion: ShippingGuide::MOTIVO_LABELS[$guide->motivo_traslado],
            origen: $guide->origen,
            destino: $guide->destino,
            destinatarioTipoDoc: $tipoDoc,
            destinatarioNumDoc: $numDoc,
            destinatarioNombre: $nombre,
            modalidad: $guide->modalidad,
            pesoTotal: (float) $guide->peso_total,
            items: $items,
            transportistaRazonSocial: $guide->transportista_razon_social,
            transportistaRuc: $guide->transportista_ruc,
            vehiculoPlaca: $guide->vehiculo_placa,
            conductorNombre: $guide->conductor_nombre,
            conductorLicencia: $guide->conductor_licencia,
            observacion: $guide->observaciones,
        );
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolveDestinatario(ShippingGuide $guide): array
    {
        if ($guide->destinatarioClient !== null) {
            return [
                $this->clientTipoDocCatalog6($guide->destinatarioClient),
                $guide->destinatarioClient->numero_documento,
                $guide->destinatarioClient->razon_social,
            ];
        }

        return [
            '0',
            $guide->destinatario_documento ?? '-',
            $guide->destinatario_nombre ?? '-',
        ];
    }

    /**
     * SUNAT Catalog 06 (Tipo de Documento de Identidad).
     */
    private function clientTipoDocCatalog6(Client $client): string
    {
        return match ($client->tipo_documento) {
            'ruc' => '6',
            'dni' => '1',
            'ce' => '4',
            'pasaporte' => '7',
            default => '0',
        };
    }
}
