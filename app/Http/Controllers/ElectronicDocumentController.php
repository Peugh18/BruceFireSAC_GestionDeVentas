<?php

namespace App\Http\Controllers;

use App\Http\Requests\RetryElectronicDocumentRequest;
use App\Http\Requests\StoreElectronicDocumentRequest;
use App\Models\ElectronicDocument;
use App\Models\Sale;
use App\Services\Billing\BillingService;
use Illuminate\Http\RedirectResponse;

class ElectronicDocumentController extends Controller
{
    public function __construct(private readonly BillingService $billingService) {}

    /**
     * Issue the electronic document (factura/boleta) for a sale. The actual
     * submission to SUNAT is queued, not performed synchronously here.
     */
    public function store(StoreElectronicDocumentRequest $request, Sale $sale): RedirectResponse
    {
        $this->billingService->issue($sale);

        return back()->with('status', 'Comprobante electronico en cola de envio a SUNAT.');
    }

    /**
     * Re-queue the submission of a document that is currently in error.
     */
    public function retry(RetryElectronicDocumentRequest $request, ElectronicDocument $electronicDocument): RedirectResponse
    {
        $this->billingService->retry($electronicDocument);

        return back()->with('status', 'Reintento de envio a SUNAT en cola.');
    }
}
