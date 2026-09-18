<?php

namespace App\Services\Billing;

use App\Services\Billing\Data\SaleDocumentData;
use App\Services\Billing\Data\SunatSendResult;
use Greenter\Model\Client\Client as GreenterClient;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Response\BillResult;
use Greenter\Model\Sale\Cuota;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\FormaPagos\FormaPagoCredito;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\SaleDetail;
use Greenter\See;
use Throwable;

/**
 * The only class in the application allowed to know about Greenter. It
 * turns a neutral SaleDocumentData into Greenter's model classes, signs and
 * sends it to SUNAT, and translates the response back into a plain
 * SunatSendResult that the rest of the billing module can consume.
 */
class GreenterService
{
    private ?See $see = null;

    public function send(SaleDocumentData $data): SunatSendResult
    {
        try {
            $invoice = $this->buildInvoice($data);
            $see = $this->see();

            $result = $see->send($invoice);
            $xml = $see->getFactory()->getLastXml();

            return $this->mapResult($result, $xml);
        } catch (Throwable $e) {
            return SunatSendResult::failed($e->getMessage());
        }
    }

    private function see(): See
    {
        if ($this->see !== null) {
            return $this->see;
        }

        $see = new See;
        $see->setService(config('billing.sunat.endpoint'));
        $see->setClaveSOL(
            config('billing.sunat.ruc'),
            config('billing.sunat.sol_user'),
            config('billing.sunat.sol_pass'),
        );

        $certPath = config('billing.sunat.cert_path');
        if ($certPath && is_string($certPath) && file_exists($certPath)) {
            $see->setCertificate(file_get_contents($certPath));
        }

        return $this->see = $see;
    }

    private function buildInvoice(SaleDocumentData $data): Invoice
    {
        $company = (new Company)
            ->setRuc(config('billing.sunat.ruc'))
            ->setRazonSocial(config('billing.company.razon_social'))
            ->setNombreComercial(config('billing.company.nombre_comercial'))
            ->setAddress(
                (new Address)
                    ->setUbigueo(config('billing.company.ubigeo'))
                    ->setDepartamento(config('billing.company.departamento'))
                    ->setProvincia(config('billing.company.provincia'))
                    ->setDistrito(config('billing.company.distrito'))
                    ->setDireccion(config('billing.company.direccion'))
            );

        $client = (new GreenterClient)
            ->setTipoDoc($data->clientTipoDoc)
            ->setNumDoc($data->clientNumDoc)
            ->setRznSocial($data->clientRznSocial);

        $details = array_map(function ($item) {
            return (new SaleDetail)
                ->setCodProducto($item->codProducto)
                ->setUnidad($item->unidad)
                ->setCantidad($item->cantidad)
                ->setDescripcion($item->descripcion)
                ->setMtoValorUnitario($item->mtoValorUnitario)
                ->setMtoValorVenta($item->mtoValorVenta)
                ->setMtoPrecioUnitario($item->mtoPrecioUnitario)
                ->setMtoBaseIgv($item->mtoBaseIgv)
                ->setPorcentajeIgv($item->porcentajeIgv)
                ->setIgv($item->igv)
                ->setTipAfeIgv($item->tipAfeIgv)
                ->setTotalImpuestos($item->igv);
        }, $data->items);

        $invoice = (new Invoice)
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101')
            ->setTipoDoc($data->tipoDoc)
            ->setSerie($data->serie)
            ->setCorrelativo($data->correlativo)
            ->setFechaEmision($data->fechaEmision)
            ->setFormaPago($this->buildFormaPago($data))
            ->setTipoMoneda('PEN')
            ->setCompany($company)
            ->setClient($client)
            ->setMtoOperGravadas($data->mtoOperGravadas)
            ->setMtoIGV($data->mtoIGV)
            ->setTotalImpuestos($data->totalImpuestos)
            ->setValorVenta($data->valorVenta)
            ->setSubTotal($data->subTotal)
            ->setMtoImpVenta($data->mtoImpVenta)
            ->setDetails($details)
            ->setObservacion($data->observacion);

        if ($data->condicionPago === 'credito' && $data->cuotas !== []) {
            $invoice->setCuotas(array_map(
                fn (array $cuota) => (new Cuota)
                    ->setMoneda('PEN')
                    ->setMonto($cuota['monto'])
                    ->setFechaPago($cuota['fecha']),
                $data->cuotas
            ));
        }

        return $invoice;
    }

    private function buildFormaPago(SaleDocumentData $data): FormaPagoContado|FormaPagoCredito
    {
        if ($data->condicionPago === 'credito') {
            return new FormaPagoCredito($data->mtoImpVenta, 'PEN');
        }

        return new FormaPagoContado;
    }

    private function mapResult(?BillResult $result, ?string $xml): SunatSendResult
    {
        if ($result === null) {
            return SunatSendResult::failed('SUNAT no devolvio una respuesta.', $xml);
        }

        if (! $result->isSuccess()) {
            $error = $result->getError();
            $message = $error !== null
                ? "[{$error->getCode()}] {$error->getMessage()}"
                : 'Error desconocido al enviar el comprobante a SUNAT.';

            return SunatSendResult::failed($message, $xml);
        }

        $cdr = $result->getCdrResponse();

        return new SunatSendResult(
            success: true,
            xml: $xml,
            cdrZip: $result->getCdrZip(),
            code: $cdr?->getCode(),
            description: $cdr?->getDescription(),
        );
    }
}
