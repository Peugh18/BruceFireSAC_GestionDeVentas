<?php

namespace App\Notifications;

use App\Models\Deficiency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Sent when a deficiency requires authorization from sales/admin.
 */
class DeficiencyPendingAuthorizationNotification extends Notification implements ShouldQueue
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
            'type' => 'deficiency_pending_authorization',
            'title' => 'Deficiencia requiere autorización',
            'message' => "La deficiencia en '{$this->deficiency->componente}' ({$orderCodigo}) requiere tu autorización.",
            'deficiency_id' => $this->deficiency->id,
            'service_order_id' => $this->deficiency->service_order_id,
            'url' => route('deficiencies.index'),
        ];
    }
}
