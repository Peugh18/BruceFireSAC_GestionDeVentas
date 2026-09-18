<?php

namespace App\Services\Billing;

use App\Models\ElectronicDocument;
use App\Services\Billing\Data\SunatSendResult;
use Illuminate\Support\Facades\Storage;

/**
 * Translates a SunatSendResult into persisted state on an ElectronicDocument:
 * stores the XML/CDR files on disk (never inline in the database) and
 * updates estado/respuesta_sunat/error/hash/fecha_envio accordingly.
 */
class SunatResponseService
{
    public function apply(ElectronicDocument $document, SunatSendResult $result): void
    {
        $updates = [
            'fecha_envio' => now(),
        ];

        if ($result->xml !== null) {
            $updates['xml_path'] = $this->storeXml($document, $result->xml);
            $updates['hash'] = hash('sha256', $result->xml);
        }

        if (! $result->success) {
            $updates['estado'] = 'error';
            $updates['error'] = $result->errorMessage;
            $document->update($updates);

            return;
        }

        if ($result->cdrZip !== null) {
            $updates['cdr_path'] = $this->storeCdr($document, $result->cdrZip);
        }

        $updates['respuesta_sunat'] = trim("{$result->code} - {$result->description}", ' -');
        $updates['error'] = null;

        if ($result->isAccepted()) {
            $updates['estado'] = 'aceptado';
        } else {
            $updates['estado'] = 'rechazado';
        }

        $document->update($updates);
    }

    private function storeXml(ElectronicDocument $document, string $xml): string
    {
        $path = "billing/xml/{$document->tipo}-{$document->serie}-{$document->correlativo}.xml";
        Storage::disk('local')->put($path, $xml);

        return $path;
    }

    private function storeCdr(ElectronicDocument $document, string $cdrZip): string
    {
        $path = "billing/cdr/{$document->tipo}-{$document->serie}-{$document->correlativo}.zip";
        Storage::disk('local')->put($path, $cdrZip);

        return $path;
    }
}
