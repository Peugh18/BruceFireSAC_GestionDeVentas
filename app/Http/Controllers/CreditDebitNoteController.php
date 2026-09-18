<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCreditDebitNoteRequest;
use App\Models\ElectronicDocument;
use App\Services\Billing\CreditDebitNoteService;
use Illuminate\Http\RedirectResponse;

class CreditDebitNoteController extends Controller
{
    public function __construct(private readonly CreditDebitNoteService $creditDebitNoteService) {}

    /**
     * Issue a nota de credito/debito for an already accepted comprobante
     * and queue its submission to SUNAT.
     */
    public function store(StoreCreditDebitNoteRequest $request, ElectronicDocument $electronicDocument): RedirectResponse
    {
        $this->creditDebitNoteService->issue($electronicDocument, $request->validated());

        return back()->with('status', 'Nota de credito/debito generada y en cola de envio a SUNAT.');
    }
}
