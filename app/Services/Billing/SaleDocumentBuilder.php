<?php

namespace App\Services\Billing;

use App\Models\Client;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Billing\Data\SaleDocumentData;
use App\Services\Billing\Data\SaleDocumentItemData;
use DateTimeImmutable;

/**
 * Shared logic for turning a Sale into the neutral SaleDocumentData used by
 * GreenterService. Factura and boleta share the same SUNAT structure (both
 * map to Greenter's Invoice model) and only differ in tipoDoc/serie, so
 * InvoiceBuilder and ReceiptBuilder only override tipoDoc().
 */
abstract class SaleDocumentBuilder
{
    private const IGV_RATE = 0.18;

    abstract public function tipoDoc(): string;

    public function build(Sale $sale, string $serie, string $correlativo): SaleDocumentData
    {
        $sale->loadMissing(['client', 'items.catalogItem']);

        $items = $sale->items->map(fn ($item) => $this->buildItem($item))->all();

        return new SaleDocumentData(
            tipoDoc: $this->tipoDoc(),
            serie: $serie,
            correlativo: $correlativo,
            fechaEmision: new DateTimeImmutable($sale->fecha->toDateString()),
            clientTipoDoc: $this->clientTipoDocCatalog6($sale->client),
            clientNumDoc: $sale->client->numero_documento,
            clientRznSocial: $sale->client->razon_social,
            clientDireccion: $sale->client->direccion_fiscal,
            condicionPago: $sale->condicion_pago,
            mtoOperGravadas: (float) $sale->subtotal,
            mtoIGV: (float) $sale->igv,
            totalImpuestos: (float) $sale->igv,
            valorVenta: (float) $sale->subtotal,
            subTotal: (float) $sale->total,
            mtoImpVenta: (float) $sale->total,
            items: $items,
            cuotas: $this->buildCuotas($sale),
            observacion: $sale->observaciones,
        );
    }

    /**
     * @return list<array{monto: float, fecha: DateTimeImmutable}>
     */
    private function buildCuotas(Sale $sale): array
    {
        if ($sale->condicion_pago !== 'credito') {
            return [];
        }

        return $sale->installments
            ->map(fn ($installment) => [
                'monto' => (float) $installment->monto,
                'fecha' => new DateTimeImmutable($installment->fecha_vencimiento),
            ])
            ->all();
    }

    /**
     * Every item is treated as gravado (IGV 18%): the rest of the app
     * (Sale/Quote totals) already computes taxes this way at the sale
     * level regardless of CatalogItem::aplica_igv, so per-item totals
     * must match that same assumption to keep the XML internally
     * consistent with Sale::subtotal/igv/total.
     */
    private function buildItem(SaleItem $item): SaleDocumentItemData
    {
        $cantidad = (float) $item->cantidad;
        $precioUnitario = (float) $item->precio_unitario;
        $descuento = (float) $item->descuento;

        $mtoValorVenta = round(($cantidad * $precioUnitario) - $descuento, 2);
        $mtoValorUnitario = $cantidad > 0 ? round($mtoValorVenta / $cantidad, 2) : $precioUnitario;
        $igv = round($mtoValorVenta * self::IGV_RATE, 2);
        $mtoPrecioUnitario = $cantidad > 0 ? round(($mtoValorVenta + $igv) / $cantidad, 2) : $precioUnitario;

        $unidadDescriptiva = $item->catalogItem->unidad ?? 'Unidad';
        $esServicio = ($item->catalogItem->tipo ?? null) === 'servicio';

        return new SaleDocumentItemData(
            unidad: $esServicio ? 'ZZ' : 'NIU',
            cantidad: $cantidad,
            codProducto: $item->catalogItem->codigo ?? (string) $item->catalog_item_id,
            descripcion: $item->catalogItem->nombre ?? $unidadDescriptiva,
            mtoValorUnitario: $mtoValorUnitario,
            mtoValorVenta: $mtoValorVenta,
            mtoPrecioUnitario: $mtoPrecioUnitario,
            mtoBaseIgv: $mtoValorVenta,
            porcentajeIgv: self::IGV_RATE * 100,
            igv: $igv,
            tipAfeIgv: '10',
        );
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
