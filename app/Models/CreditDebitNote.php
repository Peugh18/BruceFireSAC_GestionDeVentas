<?php

namespace App\Models;

use Database\Factories\CreditDebitNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditDebitNote extends Model
{
    /** @use HasFactory<CreditDebitNoteFactory> */
    use HasFactory;

    public const TIPO_LABELS = [
        'nota_credito' => 'Nota de Crédito',
        'nota_debito' => 'Nota de Débito',
    ];

    public const ESTADO_LABELS = [
        'pendiente' => 'Pendiente',
        'aceptado' => 'Aceptado',
        'rechazado' => 'Rechazado',
        'error' => 'Error',
    ];

    /**
     * SUNAT Catalog 09 (Motivo Nota de Credito).
     *
     * @var array<string, string>
     */
    public const MOTIVO_CREDITO_LABELS = [
        '01' => 'Anulación de la operación',
        '02' => 'Anulación por error en el RUC',
        '03' => 'Corrección por error en la descripción',
        '04' => 'Descuento global',
        '05' => 'Descuento por ítem',
        '06' => 'Devolución total',
        '07' => 'Devolución por ítem',
        '08' => 'Bonificación',
        '09' => 'Disminución en el valor',
        '10' => 'Otros conceptos',
    ];

    /**
     * SUNAT Catalog 10 (Motivo Nota de Debito).
     *
     * @var array<string, string>
     */
    public const MOTIVO_DEBITO_LABELS = [
        '01' => 'Intereses por mora',
        '02' => 'Aumento en el valor',
        '03' => 'Penalidades / otros conceptos',
        '10' => 'Otros cargos',
    ];

    /** @var list<string> */
    protected $fillable = [
        'cpe_afectado_id',
        'tipo',
        'motivo',
        'detalle',
        'importe',
        'fecha',
        'serie',
        'correlativo',
        'xml_path',
        'cdr_path',
        'hash',
        'estado',
        'respuesta_sunat',
        'error',
        'intentos',
        'fecha_envio',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'importe' => 'decimal:2',
            'intentos' => 'integer',
            'fecha_envio' => 'datetime',
        ];
    }

    /** @return BelongsTo<ElectronicDocument, $this> */
    public function cpeAfectado(): BelongsTo
    {
        return $this->belongsTo(ElectronicDocument::class, 'cpe_afectado_id');
    }

    public function numeroCompleto(): string
    {
        return "{$this->serie}-{$this->correlativo}";
    }

    public static function motivoLabels(string $tipo): array
    {
        return $tipo === 'nota_debito' ? self::MOTIVO_DEBITO_LABELS : self::MOTIVO_CREDITO_LABELS;
    }

    /**
     * Generate the next sequential correlativo for a given tipo/serie combination.
     */
    public static function nextCorrelativo(string $tipo, string $serie): string
    {
        $lastCorrelativo = self::query()
            ->where('tipo', $tipo)
            ->where('serie', $serie)
            ->orderByDesc('correlativo')
            ->value('correlativo');

        $lastNumber = $lastCorrelativo ? (int) $lastCorrelativo : 0;

        return sprintf('%08d', $lastNumber + 1);
    }
}
