<?php

namespace App\Models;

use Database\Factories\ServiceOrderPickupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ServiceOrderPickup extends Model implements HasMedia
{
    /** @use HasFactory<ServiceOrderPickupFactory> */
    use HasFactory, InteractsWithMedia, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'recogido_por_user_id',
                'recibido_planta_user_id',
                'entregado_por_user_id',
                'recibido_cliente_por_user_id',
            ])
            ->logOnlyDirty();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'service_order_id',
        'client_id',
        'client_site_id',
        'contacto',
        'fecha_hora_recojo',
        'cantidad',
        'observaciones',
        'conforme_nombre',
        'conforme_dni',
        'conforme_firma',
        'recogido_por_user_id',
        'recogido_en',
        'recibido_planta_user_id',
        'recibido_planta_en',
        'entregado_por_user_id',
        'entregado_en',
        'recibido_cliente_por_user_id',
        'recibido_cliente_en',
        'recibido_cliente_nombre',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_hora_recojo' => 'datetime',
            'recogido_en' => 'datetime',
            'recibido_planta_en' => 'datetime',
            'entregado_en' => 'datetime',
            'recibido_cliente_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ServiceOrder, $this>
     */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
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
    public function site(): BelongsTo
    {
        return $this->belongsTo(ClientSite::class, 'client_site_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recogidoPorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recogido_por_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recibidoPlantaUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_planta_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function entregadoPorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entregado_por_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recibidoClientePorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_cliente_por_user_id');
    }

    /**
     * Advance custody step in chain.
     */
    public function advanceCustodyStep(string $step, User $actor, ?string $extraInfo = null): void
    {
        $now = now();

        match ($step) {
            'recibido_planta' => $this->update([
                'recibido_planta_user_id' => $actor->id,
                'recibido_planta_en' => $now,
            ]),
            'entregado' => $this->update([
                'entregado_por_user_id' => $actor->id,
                'entregado_en' => $now,
            ]),
            'recibido_cliente' => $this->update([
                'recibido_cliente_por_user_id' => $actor->id,
                'recibido_cliente_en' => $now,
                'recibido_cliente_nombre' => $extraInfo ?? $this->conforme_nombre,
            ]),
            default => null,
        };
    }
}
