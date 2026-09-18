<?php

namespace App\Jobs;

use App\Models\ElectronicDocument;
use App\Services\Billing\BillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendElectronicDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly ElectronicDocument $document) {}

    public function handle(BillingService $billingService): void
    {
        $billingService->send($this->document);
    }
}
