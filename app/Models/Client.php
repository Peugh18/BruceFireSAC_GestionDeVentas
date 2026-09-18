<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
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
        'tipo_documento',
        'numero_documento',
        'razon_social',
        'nombre_comercial',
        'telefono',
        'whatsapp',
        'email',
        'direccion_fiscal',
        'departamento',
        'provincia',
        'distrito',
        'ubigeo',
        'activo',
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
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ClientSite, $this>
     */
    public function sites(): HasMany
    {
        return $this->hasMany(ClientSite::class);
    }

    /**
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            if (empty($client->codigo)) {
                $client->codigo = self::nextCodigo();
            }
        });
    }

    /**
     * Generate the next sequential internal client code (e.g. CLI-000001).
     */
    public static function nextCodigo(): string
    {
        $lastCodigo = self::query()
            ->where('codigo', 'like', 'CLI-%')
            ->orderByDesc('codigo')
            ->value('codigo');

        $lastNumber = $lastCodigo ? (int) substr($lastCodigo, 4) : 0;

        return sprintf('CLI-%06d', $lastNumber + 1);
    }
}
