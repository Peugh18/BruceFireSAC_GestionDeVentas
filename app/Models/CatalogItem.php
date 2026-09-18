<?php

namespace App\Models;

use Database\Factories\CatalogItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class CatalogItem extends Model
{
    /** @use HasFactory<CatalogItemFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'tipo', 'categoria', 'nombre', 'descripcion', 'unidad', 'precio',
        'aplica_igv', 'activo', 'controla_stock', 'control_serializado',
        'genera_barcode', 'tipo_tecnico', 'requiere_orden',
        'requiere_certificado', 'checklist_aplicable',
    ];

    protected static function booted(): void
    {
        static::creating(function (CatalogItem $item): void {
            $item->codigo = 'CAT-'.Str::ulid();
        });

        static::saving(function (CatalogItem $item): void {
            if ($item->tipo !== 'producto') {
                $item->controla_stock = false;
                $item->control_serializado = false;
                $item->genera_barcode = false;
            }

            if ($item->tipo !== 'servicio') {
                $item->tipo_tecnico = null;
                $item->requiere_orden = false;
                $item->requiere_certificado = false;
                $item->checklist_aplicable = null;
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'aplica_igv' => 'boolean',
            'activo' => 'boolean',
            'controla_stock' => 'boolean',
            'control_serializado' => 'boolean',
            'genera_barcode' => 'boolean',
            'requiere_orden' => 'boolean',
            'requiere_certificado' => 'boolean',
        ];
    }

    /** @return HasOne<InventoryStock, $this> */
    public function inventoryStock(): HasOne
    {
        return $this->hasOne(InventoryStock::class);
    }
}
