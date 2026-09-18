<?php

namespace App\Services\Billing;

use App\Models\Client;
use App\Models\CreditDebitNote;
use App\Services\Billing\Data\CreditDebitNoteData;
use DateTimeImmutable;

/**
 * Turns a CreditDebitNote (and the CPE it affects) into the neutral
 * CreditDebitNoteData consumed by GreenterService::sendNote(). Nothing here
 * depends on Greenter.
 */
class CreditDebitNoteBuilder
{
    private const IGV_RATE = 0.18;

    public function build(CreditDebitNote $note): CreditDebitNoteData
    {
        $note->loadMissing('cpeAfectado.sale.client');

        $cpe = $note->cpeAfectado;
        $client = $cpe->sale->client;

        $importe = (float) $note->importe;
        $valorVenta = round($importe / (1 + self::IGV_RATE), 2);
        $igv = round($importe - $valorVenta, 2);

        return new CreditDebitNoteData(
            tipoDoc: $note->tipo === 'nota_credito' ? '07' : '08',
            serie: $note->serie,
            correlativo: $note->correlativo,
            fechaEmision: new DateTimeImmutable($note->fecha->toDateString()),
            codMotivo: $note->motivo,
            desMotivo: CreditDebitNote::motivoLabels($note->tipo)[$note->motivo] ?? $note->detalle,
            tipDocAfectado: $cpe->tipo === 'factura' ? '01' : '03',
            numDocAfectado: $cpe->numeroCompleto(),
            clientTipoDoc: $this->clientTipoDocCatalog6($client),
            clientNumDoc: $client->numero_documento,
            clientRznSocial: $client->razon_social,
            mtoOperGravadas: $valorVenta,
            mtoIGV: $igv,
            valorVenta: $valorVenta,
            subTotal: $importe,
            mtoImpVenta: $importe,
            descripcion: $note->detalle,
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
