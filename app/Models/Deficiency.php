<?php

namespace App\Models;

use App\Notifications\DeficiencyAuthorizedNotification;
use App\Notifications\DeficiencyPendingAuthorizationNotification;
use Database\Factories\DeficiencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Deficiency extends Model
{
    /** @use HasFactory<DeficiencyFactory> */
    use HasFactory;

    public const STATUS_LABELS = [
        'detectada' => 'Detectada',
        'esperando_autorizacion' => 'Esperando autorización',
        'autorizada' => 'Autorizada',
        'rechazada' => 'Rechazada',
        'en_correccion' => 'En corrección',
        'resuelta' => 'Resuelta',
    ];

    /** @var array<string, list<string>> */
    public const ALLOWED_TRANSITIONS = [
        'detectada' => ['esperando_autorizacion', 'autorizada', 'rechazada'],
        'esperando_autorizacion' => ['autorizada', 'rechazada'],
        'autorizada' => ['en_correccion', 'resuelta'],
        'rechazada' => [],
        'en_correccion' => ['resuelta'],
        'resuelta' => [],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'service_order_id',
        'equipment_id',
        'checklist_item_id',
        'componente',
        'condicion',
        'foto_path',
        'nota',
        'accion_recomendada',
        'repuesto_sugerido',
        'catalog_item_id',
        'requiere_autorizacion',
        'estado',
        'resolucion',
        'resuelto_por_user_id',
        'resuelto_en',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requiere_autorizacion' => 'boolean',
            'resuelto_en' => 'datetime',
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
     * @return BelongsTo<Equipment, $this>
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * @return BelongsTo<ChecklistItem, $this>
     */
    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    /**
     * @return BelongsTo<CatalogItem, $this>
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resueltoPorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por_user_id');
    }

    /**
     * @return HasMany<DeficiencyAuthorization, $this>
     */
    public function authorizations(): HasMany
    {
        return $this->hasMany(DeficiencyAuthorization::class);
    }

    /**
     * Change state respecting valid transitions and register EquipmentEvent when resolved.
     */
    public function transitionTo(string $newStatus, User $actor, ?string $resolucionNote = null): void
    {
        DB::transaction(function () use ($newStatus, $actor, $resolucionNote): void {
            $allowed = self::ALLOWED_TRANSITIONS[$this->estado] ?? [];

            if (! in_array($newStatus, $allowed, true)) {
                throw ValidationException::withMessages([
                    'estado' => "Transición no válida de '{$this->estado}' a '{$newStatus}'.",
                ]);
            }

            $updates = ['estado' => $newStatus];

            if ($resolucionNote !== null) {
                $updates['resolucion'] = $resolucionNote;
            }

            if ($newStatus === 'resuelta') {
                $updates['resuelto_por_user_id'] = $actor->id;
                $updates['resuelto_en'] = now();

                // Create EquipmentEvent in equipment timeline
                EquipmentEvent::create([
                    'equipment_id' => $this->equipment_id,
                    'tipo' => 'deficiencia_resuelta',
                    'descripcion' => "Deficiencia resuelta en {$this->componente}: ".($resolucionNote ?? $this->accion_recomendada ?? 'Corrección realizada'),
                    'fecha' => now()->toDateString(),
                    'user_id' => $actor->id,
                ]);
            }

            $this->update($updates);
        });

        // Notifications dispatched outside the transaction so they only fire
        // after the state change is committed.
        if ($newStatus === 'esperando_autorizacion') {
            // Notify every user who holds the deficiencies.authorize permission.
            User::permission('deficiencies.authorize')->each(
                fn (User $u) => $u->notify(new DeficiencyPendingAuthorizationNotification($this))
            );
        }

        if ($newStatus === 'autorizada') {
            // Notify the technician assigned to the service order so they
            // can proceed with the correction.
            $this->loadMissing('serviceOrder.tecnico');
            $tecnico = $this->serviceOrder?->tecnico;

            if ($tecnico && $tecnico->id !== $actor->id) {
                $tecnico->notify(new DeficiencyAuthorizedNotification($this));
            }
        }
    }
}
