<?php

namespace App\Services\Billing;

use App\Jobs\SendElectronicDocumentJob;
use App\Models\Client;
use App\Models\ElectronicDocument;
use App\Models\Sale;
use Illuminate\Validation\ValidationException;

/**
 * Entry point for the billing module (§31 architecture): Sale -> BillingService
 * -> InvoiceBuilder/ReceiptBuilder -> GreenterService -> SUNAT -> CDR/estado/error.
 */
class BillingService
{
    public function __construct(
        private readonly InvoiceBuilder $invoiceBuilder,
        private readonly ReceiptBuilder $receiptBuilder,
        private readonly GreenterService $greenterService,
        private readonly SunatResponseService $responseService,
    ) {}

    /**
     * Create (or reuse) the electronic document for a sale and queue its
     * submission to SUNAT. The actual send happens on the queue, never
     * synchronously within the request.
     */
    public function issue(Sale $sale): ElectronicDocument
    {
        $document = ElectronicDocument::firstOrNew(['sale_id' => $sale->id]);

        if (! $document->exists) {
            $tipo = self::resolveTipo($sale->client);
            $serie = config("billing.series.{$tipo}");

            $document->fill([
                'sale_id' => $sale->id,
                'tipo' => $tipo,
                'serie' => $serie,
                'correlativo' => ElectronicDocument::nextCorrelativo($tipo, $serie),
                'estado' => 'pendiente',
                'intentos' => 0,
            ]);
            $document->save();
        }

        SendElectronicDocumentJob::dispatch($document);

        return $document;
    }

    /**
     * Re-queue the submission of a document that previously failed.
     */
    public function retry(ElectronicDocument $document): void
    {
        if ($document->estado !== 'error') {
            throw ValidationException::withMessages([
                'estado' => 'Solo se puede reintentar el envio de comprobantes en estado error.',
            ]);
        }

        SendElectronicDocumentJob::dispatch($document);
    }

    /**
     * Build, sign and send the document to SUNAT, then persist the result.
     * Called from the queued job — never call this synchronously from a
     * request.
     */
    public function send(ElectronicDocument $document): void
    {
        $document->increment('intentos');

        $builder = $document->tipo === 'factura' ? $this->invoiceBuilder : $this->receiptBuilder;
        $data = $builder->build($document->sale, $document->serie, $document->correlativo);

        $result = $this->greenterService->send($data);

        $this->responseService->apply($document, $result);
    }

    /**
     * Factura for RUC (empresa), boleta for any other document type
     * (persona natural).
     */
    public static function resolveTipo(Client $client): string
    {
        return $client->tipo_documento === 'ruc' ? 'factura' : 'boleta';
    }
}
