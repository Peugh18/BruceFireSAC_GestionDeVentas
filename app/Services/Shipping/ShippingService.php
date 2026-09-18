<?php

namespace App\Services\Shipping;

use App\Jobs\SendShippingGuideJob;
use App\Models\ShippingGuide;
use App\Services\Billing\GreenterService;
use Illuminate\Validation\ValidationException;

/**
 * Entry point for the GRE module (§30/§31 architecture): ShippingGuide ->
 * ShippingService -> ShippingGuideBuilder -> GreenterService -> SUNAT ->
 * CDR/estado/error. Kept as its own service (not folded into BillingService)
 * because GRE uses a different Greenter flow/API (Despatch, not Invoice),
 * per §31: "Para GRE se mantendra un servicio separado".
 */
class ShippingService
{
    public function __construct(
        private readonly ShippingGuideBuilder $builder,
        private readonly GreenterService $greenterService,
        private readonly ShippingResponseService $responseService,
    ) {}

    /**
     * Queue the submission of a freshly created shipping guide to SUNAT.
     * The actual send happens on the queue, never synchronously within the
     * request.
     */
    public function issue(ShippingGuide $guide): void
    {
        SendShippingGuideJob::dispatch($guide);
    }

    /**
     * Re-queue the submission of a guide that previously failed.
     */
    public function retry(ShippingGuide $guide): void
    {
        if ($guide->estado !== 'error') {
            throw ValidationException::withMessages([
                'estado' => 'Solo se puede reintentar el envio de guias en estado error.',
            ]);
        }

        SendShippingGuideJob::dispatch($guide);
    }

    /**
     * Build, sign and send the guide to SUNAT, then persist the result.
     * Called from the queued job — never call this synchronously from a
     * request.
     */
    public function send(ShippingGuide $guide): void
    {
        $guide->increment('intentos');

        $data = $this->builder->build($guide);
        $result = $this->greenterService->sendDespatch($data);

        $this->responseService->apply($guide, $result);
    }
}
