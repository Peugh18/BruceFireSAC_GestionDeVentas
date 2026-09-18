<?php

namespace App\Models;

use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['estado'])->logOnlyDirty();
    }

    public const TIPO_LABELS = [
        'operatividad_garantia' => 'Operatividad y Garantía',
        'prueba_hidrostatica' => 'Prueba Hidrostática',
        'capacitacion' => 'Capacitación / Participación',
        'deteccion_alarma' => 'Operatividad de Detección/Alarma',
        'otro' => 'Otro',
    ];

    public const ESTADO_LABELS = [
        'vigente' => 'Vigente',
        'vencido' => 'Vencido',
        'reemplazado' => 'Reemplazado',
        'anulado' => 'Anulado',
    ];

    /** @var array<string, list<string>> */
    public const ALLOWED_TRANSITIONS = [
        'vigente' => ['vencido', 'reemplazado', 'anulado'],
        'vencido' => ['reemplazado', 'anulado'],
        'reemplazado' => [],
        'anulado' => [],
    ];

    /** @var list<string> */
    protected $fillable = [
        'service_order_id',
        'tipo',
        'estado',
        'fecha_emision',
        'fecha_vigencia',
        'observaciones',
        'generado_por_user_id',
    ];

    /** @var array<string, string> */
    protected $attributes = ['estado' => 'vigente'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date:Y-m-d',
            'fecha_vigencia' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Certificate $certificate): void {
            if (empty($certificate->numero)) {
                $certificate->numero = self::nextNumero();
            }

            if (empty($certificate->token)) {
                $certificate->token = self::generateToken();
            }
        });
    }

    /**
     * Generate the next sequential BRUCE FIRE certificate number (e.g. CERT-000123).
     */
    public static function nextNumero(): string
    {
        $lastNumero = self::query()
            ->where('numero', 'like', 'CERT-%')
            ->orderByDesc('numero')
            ->value('numero');

        $lastNumber = $lastNumero ? (int) substr($lastNumero, 5) : 0;

        return sprintf('CERT-%06d', $lastNumber + 1);
    }

    /**
     * Generate an unpredictable public verification token.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::query()->where('token', $token)->exists());

        return $token;
    }

    /** @return BelongsTo<ServiceOrder, $this> */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    /** @return BelongsTo<User, $this> */
    public function generadoPorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por_user_id');
    }

    /** @return HasMany<CertificateItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CertificateItem::class);
    }

    /** @return BelongsToMany<Equipment, $this> */
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'certificate_items');
    }

    public function isVigente(): bool
    {
        if ($this->estado !== 'vigente') {
            return false;
        }

        return $this->fecha_vigencia === null || ! $this->fecha_vigencia->isPast();
    }

    /**
     * Change the certificate status respecting the allowed state machine.
     */
    public function transitionTo(string $status): void
    {
        $allowed = self::ALLOWED_TRANSITIONS[$this->estado] ?? [];

        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'estado' => "Transición no válida de '{$this->estado}' a '{$status}'.",
            ]);
        }

        $this->update(['estado' => $status]);
    }
}
