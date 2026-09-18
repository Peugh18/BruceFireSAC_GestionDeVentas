<?php

namespace App\Models;

use Database\Factories\ElectronicDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectronicDocument extends Model
{
    /** @use HasFactory<ElectronicDocumentFactory> */
    use HasFactory;

    public const TIPO_LABELS = [
        'factura' => 'Factura',
        'boleta' => 'Boleta',
    ];

    public const ESTADO_LABELS = [
        'pendiente' => 'Pendiente',
        'aceptado' => 'Aceptado',
        'rechazado' => 'Rechazado',
        'error' => 'Error',
    ];

    /** @var list<string> */
    protected $fillable = [
        'sale_id',
        'tipo',
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
            'intentos' => 'integer',
            'fecha_envio' => 'datetime',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function numeroCompleto(): string
    {
        return "{$this->serie}-{$this->correlativo}";
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
