<?php

namespace App\Models;

use Database\Factories\ShippingGuideFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingGuide extends Model
{
    /** @use HasFactory<ShippingGuideFactory> */
    use HasFactory;

    public const MOTIVO_LABELS = [
        'venta' => 'Venta',
        'compra' => 'Compra',
        'traslado_entre_establecimientos' => 'Traslado entre establecimientos',
        'importacion' => 'Importación',
        'exportacion' => 'Exportación',
        'otros' => 'Otros',
    ];

    /**
     * SUNAT Catalog 20 (Motivo de traslado).
     *
     * @var array<string, string>
     */
    public const MOTIVO_SUNAT_CODES = [
        'venta' => '01',
        'compra' => '02',
        'traslado_entre_establecimientos' => '04',
        'importacion' => '08',
        'exportacion' => '09',
        'otros' => '13',
    ];

    public const MODALIDAD_LABELS = [
        'transporte_publico' => 'Transporte público',
        'transporte_privado' => 'Transporte privado',
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
        'motivo_traslado',
        'fecha_inicio',
        'origen',
        'destino',
        'destinatario_client_id',
        'destinatario_nombre',
        'destinatario_documento',
        'peso_total',
        'modalidad',
        'transportista_razon_social',
        'transportista_ruc',
        'vehiculo_placa',
        'conductor_nombre',
        'conductor_licencia',
        'observaciones',
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
            'fecha_inicio' => 'date:Y-m-d',
            'peso_total' => 'decimal:2',
            'intentos' => 'integer',
            'fecha_envio' => 'datetime',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<Client, $this> */
    public function destinatarioClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'destinatario_client_id');
    }

    /** @return HasMany<ShippingGuideItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ShippingGuideItem::class);
    }

    public function numeroCompleto(): string
    {
        return "{$this->serie}-{$this->correlativo}";
    }

    /**
     * Generate the next sequential correlativo for a given series.
     */
    public static function nextCorrelativo(string $serie): string
    {
        $lastCorrelativo = self::query()
            ->where('serie', $serie)
            ->orderByDesc('correlativo')
            ->value('correlativo');

        $lastNumber = $lastCorrelativo ? (int) $lastCorrelativo : 0;

        return sprintf('%08d', $lastNumber + 1);
    }
}
