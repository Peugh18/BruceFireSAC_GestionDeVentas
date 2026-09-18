<?php

namespace App\Notifications;

use App\Models\ServiceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Sent to the assigned technician when a new service order is created.
 */
class ServiceOrderAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ServiceOrder $serviceOrder) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'service_order_assigned',
            'title' => 'Nueva orden de servicio asignada',
            'message' => "Se te asignó la orden {$this->serviceOrder->codigo} ({$this->serviceOrder->tipo_servicio}).",
            'service_order_id' => $this->serviceOrder->id,
            'codigo' => $this->serviceOrder->codigo,
            'url' => route('service-orders.show', $this->serviceOrder->id),
        ];
    }
}
