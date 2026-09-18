<?php

namespace App\Models;

use Database\Factories\InventoryReceptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryReception extends Model
{
    /** @use HasFactory<InventoryReceptionFactory> */
    use HasFactory;

    protected $fillable = ['catalog_item_id', 'proveedor', 'documento_referencia', 'fecha', 'cantidad', 'cantidad_conforme', 'cantidad_observada', 'observacion', 'usuario_id'];

    protected function casts(): array
    {
        return ['cantidad' => 'decimal:3', 'cantidad_conforme' => 'decimal:3', 'cantidad_observada' => 'decimal:3', 'fecha' => 'date:Y-m-d'];
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(InventoryUnit::class);
    }

    public function movement(): HasOne
    {
        return $this->hasOne(InventoryMovement::class);
    }

    /** @param array{catalog_item_id: int, proveedor: string, documento_referencia: string, fecha: string, cantidad: numeric-string|float|int, cantidad_conforme: numeric-string|float|int, cantidad_observada: numeric-string|float|int, observacion?: ?string, units?: list<array{serie: string, marca: string, capacidad: string, anio: int, barcode?: ?string, conforme: bool}>} $data */
    public static function receive(array $data, User $user): self
    {
        return DB::transaction(function () use ($data, $user): self {
            $item = CatalogItem::query()->lockForUpdate()->findOrFail($data['catalog_item_id']);

            if (! $item->controla_stock || ! $item->activo) {
                throw ValidationException::withMessages(['catalog_item_id' => 'Selecciona un artículo activo que controle stock.']);
            }

            $units = $data['units'] ?? [];
            if ($item->control_serializado !== ($units !== [])) {
                throw ValidationException::withMessages(['units' => 'Las unidades deben coincidir con el control serializado del artículo.']);
            }

            $stock = InventoryStock::firstOrCreate(['catalog_item_id' => $item->id]);
            $stock = InventoryStock::query()->lockForUpdate()->findOrFail($stock->id);
            $before = (int) round((float) $stock->stock_actual * 1000);
            $after = $before + (int) round((float) $data['cantidad_conforme'] * 1000);

            if ($after > 999999999999) {
                throw ValidationException::withMessages(['cantidad_conforme' => 'La recepción excede el límite de stock permitido.']);
            }

            $reception = static::create(collect($data)->except('units')->all() + ['usuario_id' => $user->id]);

            foreach ($units as $index => $unit) {
                if (InventoryUnit::where('serie', $unit['serie'])->exists()) {
                    throw ValidationException::withMessages(["units.{$index}.serie" => 'Esta serie ya está registrada.']);
                }

                $reception->units()->create([
                    'catalog_item_id' => $item->id,
                    'serie' => $unit['serie'],
                    'marca' => $unit['marca'],
                    'capacidad' => $unit['capacidad'],
                    'anio' => $unit['anio'],
                    'barcode' => $unit['barcode'] ?? ($item->genera_barcode ? 'INV-'.Str::ulid() : null),
                    'conforme' => $unit['conforme'],
                    'en_stock' => $unit['conforme'],
                    'estado' => $unit['conforme'] ? 'disponible' : 'reservado',
                ]);
            }

            $reception->movement()->create([
                'catalog_item_id' => $item->id,
                'tipo' => 'entrada',
                'cantidad' => $data['cantidad_conforme'],
                'stock_antes' => $before / 1000,
                'stock_despues' => $after / 1000,
                'motivo' => 'Recepción de proveedor',
                'referencia' => $data['documento_referencia'],
                'fecha' => $data['fecha'],
                'observacion' => $data['observacion'] ?? null,
                'usuario_id' => $user->id,
            ]);
            $stock->update(['stock_actual' => $after / 1000]);

            return $reception;
        }, 3);
    }
}
