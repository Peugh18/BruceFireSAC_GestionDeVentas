<?php

namespace App\Notifications;

use App\Models\ElectronicDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Sent to the seller when a SUNAT electronic document send fails.
 */
class SunatErrorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ElectronicDocument $electronicDocument) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $numero = $this->electronicDocument->numeroCompleto();

        return [
            'type' => 'sunat_error',
            'title' => 'Error al enviar a SUNAT',
            'message' => "El comprobante {$numero} ({$this->electronicDocument->tipo}) falló el envío a SUNAT: {$this->electronicDocument->error}",
            'electronic_document_id' => $this->electronicDocument->id,
            'sale_id' => $this->electronicDocument->sale_id,
            'url' => route('sales.show', $this->electronicDocument->sale_id),
        ];
    }
}
