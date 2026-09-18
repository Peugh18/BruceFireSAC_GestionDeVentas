<?php

namespace App\Services\Billing;

use App\Models\CreditDebitNote;
use App\Models\ElectronicDocument;
use App\Notifications\SunatErrorNotification;
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

            // Notify the seller so they can act on the SUNAT error.
            $document->loadMissing('sale.vendedor');
            $vendedor = $document->sale?->vendedor;

            if ($vendedor) {
                $vendedor->notify(new SunatErrorNotification($document));
            }

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

    /**
     * Same as apply(), for a nota de credito/debito instead of a CPE.
     */
    public function applyToNote(CreditDebitNote $note, SunatSendResult $result): void
    {
        $updates = [
            'fecha_envio' => now(),
        ];

        if ($result->xml !== null) {
            $updates['xml_path'] = $this->storeNoteXml($note, $result->xml);
            $updates['hash'] = hash('sha256', $result->xml);
        }

        if (! $result->success) {
            $updates['estado'] = 'error';
            $updates['error'] = $result->errorMessage;
            $note->update($updates);

            return;
        }

        if ($result->cdrZip !== null) {
            $updates['cdr_path'] = $this->storeNoteCdr($note, $result->cdrZip);
        }

        $updates['respuesta_sunat'] = trim("{$result->code} - {$result->description}", ' -');
        $updates['error'] = null;
        $updates['estado'] = $result->isAccepted() ? 'aceptado' : 'rechazado';

        $note->update($updates);
    }

    private function storeNoteXml(CreditDebitNote $note, string $xml): string
    {
        $path = "billing/notes/xml/{$note->tipo}-{$note->serie}-{$note->correlativo}.xml";
        Storage::disk('local')->put($path, $xml);

        return $path;
    }

    private function storeNoteCdr(CreditDebitNote $note, string $cdrZip): string
    {
        $path = "billing/notes/cdr/{$note->tipo}-{$note->serie}-{$note->correlativo}.zip";
        Storage::disk('local')->put($path, $cdrZip);

        return $path;
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
