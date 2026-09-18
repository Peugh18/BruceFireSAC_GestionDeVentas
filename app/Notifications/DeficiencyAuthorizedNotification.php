<?php

namespace App\Notifications;

use App\Models\Deficiency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Sent to the technician assigned to an order when a deficiency is authorized
 * so they can proceed with the correction.
 */
class DeficiencyAuthorizedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Deficiency $deficiency) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $orderCodigo = $this->deficiency->serviceOrder?->codigo ?? "Orden #{$this->deficiency->service_order_id}";

        return [
            'type' => 'deficiency_authorized',
            'title' => 'Deficiencia autorizada',
            'message' => "La deficiencia en '{$this->deficiency->componente}' ({$orderCodigo}) fue autorizada. Puedes proceder con la corrección.",
            'deficiency_id' => $this->deficiency->id,
            'service_order_id' => $this->deficiency->service_order_id,
            'url' => route('deficiencies.index'),
        ];
    }
}
