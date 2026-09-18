<?php

namespace App\Services\Billing;

use App\Jobs\SendCreditDebitNoteJob;
use App\Models\CreditDebitNote;
use App\Models\ElectronicDocument;
use Illuminate\Validation\ValidationException;

/**
 * Entry point for notas de credito/debito (§29): an ElectronicDocument
 * already issued and accepted -> CreditDebitNoteService ->
 * CreditDebitNoteBuilder -> GreenterService -> SUNAT -> CDR/estado/error.
 * Notes are never created as an independent sale.
 */
class CreditDebitNoteService
{
    public function __construct(
        private readonly CreditDebitNoteBuilder $builder,
        private readonly GreenterService $greenterService,
        private readonly SunatResponseService $responseService,
    ) {}

    /**
     * @param  array{tipo: string, motivo: string, detalle: string, importe: float, fecha: string}  $data
     */
    public function issue(ElectronicDocument $cpe, array $data): CreditDebitNote
    {
        if ($cpe->estado !== 'aceptado') {
            throw ValidationException::withMessages([
                'cpe_afectado_id' => 'Solo se pueden emitir notas de credito/debito sobre comprobantes aceptados por SUNAT.',
            ]);
        }

        $serie = config("billing.note_series.{$data['tipo']}.{$cpe->tipo}");

        $note = CreditDebitNote::create([
            'cpe_afectado_id' => $cpe->id,
            'tipo' => $data['tipo'],
            'motivo' => $data['motivo'],
            'detalle' => $data['detalle'],
            'importe' => $data['importe'],
            'fecha' => $data['fecha'],
            'serie' => $serie,
            'correlativo' => CreditDebitNote::nextCorrelativo($data['tipo'], $serie),
            'estado' => 'pendiente',
        ]);

        SendCreditDebitNoteJob::dispatch($note);

        return $note;
    }

    /**
     * Build, sign and send the note to SUNAT, then persist the result.
     * Called from the queued job — never call this synchronously from a
     * request.
     */
    public function send(CreditDebitNote $note): void
    {
        $note->increment('intentos');

        $data = $this->builder->build($note);
        $result = $this->greenterService->sendNote($data);

        $this->responseService->applyToNote($note, $result);
    }
}
