<?php

namespace App\Jobs;

use App\Models\CreditDebitNote;
use App\Services\Billing\CreditDebitNoteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCreditDebitNoteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly CreditDebitNote $note) {}

    public function handle(CreditDebitNoteService $creditDebitNoteService): void
    {
        $creditDebitNoteService->send($this->note);
    }
}
