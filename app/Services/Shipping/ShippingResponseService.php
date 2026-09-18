<?php

namespace App\Services\Shipping;

use App\Models\ShippingGuide;
use App\Services\Billing\Data\SunatSendResult;
use Illuminate\Support\Facades\Storage;

/**
 * Translates a SunatSendResult into persisted state on a ShippingGuide:
 * stores the XML/CDR files on disk (never inline in the database) and
 * updates estado/respuesta_sunat/error/hash/fecha_envio accordingly. Mirrors
 * Billing\SunatResponseService for the electronic_documents table.
 */
class ShippingResponseService
{
    public function apply(ShippingGuide $guide, SunatSendResult $result): void
    {
        $updates = [
            'fecha_envio' => now(),
        ];

        if ($result->xml !== null) {
            $updates['xml_path'] = $this->storeXml($guide, $result->xml);
            $updates['hash'] = hash('sha256', $result->xml);
        }

        if (! $result->success) {
            $updates['estado'] = 'error';
            $updates['error'] = $result->errorMessage;
            $guide->update($updates);

            return;
        }

        if ($result->cdrZip !== null) {
            $updates['cdr_path'] = $this->storeCdr($guide, $result->cdrZip);
        }

        $updates['respuesta_sunat'] = trim("{$result->code} - {$result->description}", ' -');
        $updates['error'] = null;
        $updates['estado'] = $result->isAccepted() ? 'aceptado' : 'rechazado';

        $guide->update($updates);
    }

    private function storeXml(ShippingGuide $guide, string $xml): string
    {
        $path = "shipping/xml/{$guide->serie}-{$guide->correlativo}.xml";
        Storage::disk('local')->put($path, $xml);

        return $path;
    }

    private function storeCdr(ShippingGuide $guide, string $cdrZip): string
    {
        $path = "shipping/cdr/{$guide->serie}-{$guide->correlativo}.zip";
        Storage::disk('local')->put($path, $cdrZip);

        return $path;
    }
}
