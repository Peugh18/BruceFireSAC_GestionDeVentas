<?php

namespace App\Jobs;

use App\Models\ShippingGuide;
use App\Services\Shipping\ShippingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendShippingGuideJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly ShippingGuide $guide) {}

    public function handle(ShippingService $shippingService): void
    {
        $shippingService->send($this->guide);
    }
}
