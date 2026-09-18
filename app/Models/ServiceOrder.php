<?php

namespace App\Models;

use Database\Factories\ServiceOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ServiceOrder extends Model
{
    /** @use HasFactory<ServiceOrderFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['estado'])->logOnlyDirty();
    }

    public const STATUS_LABELS = [
        'pendiente_recepcion' => 'Pendiente de recepción',
        'recibido_planta' => 'Recibido en Planta',
        'en_revision' => 'En revisión',
        'esperando_autorizacion' => 'Esperando autorización',
        'autorizado' => 'Autorizado',
        'en_proceso' => 'En proceso',
        'trabajo_terminado' => 'Trabajo terminado',
        'pendiente_datos' => 'Pendiente de datos',
        'datos_completos' => 'Datos completos',
        'listo_certificado' => 'Listo para certificado',
        'listo_entrega' => 'Listo para entrega',
        'entregado' => 'Entregado',
        'cerrado' => 'Cerrado',
    ];

    public const SERVICE_TYPES = [
        'recarga' => 'Recarga',
        'mantenimiento' => 'Mantenimiento',
        'prueba_hidrostatica' => 'Prueba hidrostática',
        'inspeccion' => 'Inspección',
        'instalacion' => 'Instalación',
        'mantenimiento_campo' => 'Mantenimiento en campo',
        'otro' => 'Otro',
    ];

    public const PRIORITIES = ['baja' => 'Baja', 'media' => 'Media', 'alta' => 'Alta'];

    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'pendiente_recepcion' => ['recibido_planta'],
        'recibido_planta' => ['en_revision'],
        'en_revision' => ['esperando_autorizacion'],
        'esperando_autorizacion' => ['autorizado'],
        'autorizado' => ['en_proceso'],
        'en_proceso' => ['trabajo_terminado'],
        'trabajo_terminado' => ['pendiente_datos'],
        'pendiente_datos' => ['datos_completos'],
        'datos_completos' => ['listo_certificado'],
        'listo_certificado' => ['listo_entrega'],
        'listo_entrega' => ['entregado'],
        'entregado' => ['cerrado'],
        'cerrado' => [],
    ];

    /** @var list<string> */
    protected $fillable = [
        'client_id', 'client_site_id', 'vehicle_id', 'sale_id', 'tipo_servicio',
        'fecha', 'tecnico_user_id', 'prioridad', 'observaciones',
    ];

    /** @var array<string, string> */
    protected $attributes = ['estado' => 'pendiente_recepcion', 'prioridad' => 'media'];

    private bool $transitioning = false;

    protected static function booted(): void
    {
        static::creating(function (ServiceOrder $order): void {
            $order->codigo = 'OS-'.Str::ulid();
        });

        static::created(function (ServiceOrder $order): void {
            $order->statusHistory()->create([
                'estado' => $order->estado,
                'user_id' => auth()->id(),
            ]);
        });

        static::updating(function (ServiceOrder $order): void {
            if ($order->isDirty('estado') && ! $order->transitioning) {
                throw ValidationException::withMessages([
                    'estado' => 'Utiliza la acción de avanzar estado para conservar el historial.',
                ]);
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['fecha' => 'date:Y-m-d'];
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<ClientSite, $this> */
    public function clientSite(): BelongsTo
    {
        return $this->belongsTo(ClientSite::class);
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<User, $this> */
    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_user_id');
    }

    /** @return BelongsToMany<Equipment, $this> */
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'service_order_equipment');
    }

    /** @return HasMany<ServiceOrderStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(ServiceOrderStatusHistory::class);
    }

    /** @return HasMany<ServiceOrderPickup, $this> */
    public function pickups(): HasMany
    {
        return $this->hasMany(ServiceOrderPickup::class);
    }

    /** @return list<string> */
    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->estado] ?? [];
    }

    public static function transitionPermission(string $status): string
    {
        return match ($status) {
            'recibido_planta' => 'service_orders.receive',
            'autorizado' => 'service_orders.create',
            'cerrado' => 'service_orders.close',
            default => 'service_orders.execute',
        };
    }

    public function transitionTo(string $status, User $actor, ?string $observaciones = null): void
    {
        DB::transaction(function () use ($status, $actor, $observaciones): void {
            $order = self::query()->lockForUpdate()->findOrFail($this->id);

            if (! in_array($status, $order->allowedTransitions(), true)) {
                throw ValidationException::withMessages([
                    'estado' => 'La transición solicitada no es válida para el estado actual de la orden.',
                ]);
            }

            Gate::forUser($actor)->authorize('transition', [$order, $status]);

            $previousStatus = $order->estado;
            $order->transitioning = true;
            $order->estado = $status;
            $order->save();

            $order->statusHistory()->create([
                'estado_anterior' => $previousStatus,
                'estado' => $status,
                'user_id' => $actor->id,
                'observaciones' => $observaciones,
            ]);

            if (in_array($status, ['recibido_planta', 'autorizado', 'cerrado'], true)) {
                $label = self::STATUS_LABELS[$status];

                foreach ($order->equipment()->get() as $equipment) {
                    $equipment->events()->create([
                        'tipo' => 'cambio_estado',
                        'descripcion' => "Orden {$order->codigo}: {$label}.",
                        'fecha' => now()->toDateString(),
                        'user_id' => $actor->id,
                    ]);
                }
            }
        });

        $this->refresh();
    }
}
