<?php

namespace App\Notifications;

use App\Models\ServiceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Sent to the user who created the order when it reaches 'trabajo_terminado'.
 */
class ServiceOrderFinishedNotification extends Notification implements ShouldQueue
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
            'type' => 'service_order_finished',
            'title' => 'Trabajo terminado',
            'message' => "La orden {$this->serviceOrder->codigo} fue marcada como trabajo terminado.",
            'service_order_id' => $this->serviceOrder->id,
            'codigo' => $this->serviceOrder->codigo,
            'url' => route('service-orders.show', $this->serviceOrder->id),
        ];
    }
}
