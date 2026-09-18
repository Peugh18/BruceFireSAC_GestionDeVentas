<?php

namespace App\Models;

use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'codigo',
        'barcode',
        'client_id',
        'client_site_id',
        'vehicle_id',
        'origen',
        'tipo_equipo',
        'agente',
        'capacidad',
        'marca',
        'serie_fabricante',
        'anio_fabricacion',
        'ubicacion',
        'estado',
        'ultima_atencion',
        'proxima_atencion',
        'ultima_ph',
        'proxima_ph',
        'observaciones',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ultima_atencion' => 'date:Y-m-d',
            'proxima_atencion' => 'date:Y-m-d',
            'ultima_ph' => 'date:Y-m-d',
            'proxima_ph' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<ClientSite, $this>
     */
    public function clientSite(): BelongsTo
    {
        return $this->belongsTo(ClientSite::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return HasMany<EquipmentTransfer, $this>
     */
    public function transfers(): HasMany
    {
        return $this->hasMany(EquipmentTransfer::class);
    }

    /**
     * @return HasMany<EquipmentEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(EquipmentEvent::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Equipment $equipment) {
            if (empty($equipment->codigo)) {
                $equipment->codigo = self::nextCodigo();
            }

            if (empty($equipment->barcode)) {
                $equipment->barcode = $equipment->codigo;
            }
        });

        static::created(function (Equipment $equipment) {
            $equipment->events()->create([
                'tipo' => 'alta',
                'descripcion' => 'Alta del equipo en el sistema.',
                'fecha' => now()->toDateString(),
            ]);
        });
    }

    /**
     * Generate the next sequential BRUCE FIRE equipment code (e.g. BF-EQ-000245).
     */
    public static function nextCodigo(): string
    {
        $lastCodigo = self::query()
            ->where('codigo', 'like', 'BF-EQ-%')
            ->orderByDesc('codigo')
            ->value('codigo');

        $lastNumber = $lastCodigo ? (int) substr($lastCodigo, 6) : 0;

        return sprintf('BF-EQ-%06d', $lastNumber + 1);
    }
}
