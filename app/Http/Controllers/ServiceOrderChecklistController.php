<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChecklistRequest;
use App\Models\CatalogItem;
use App\Models\Deficiency;
use App\Models\Equipment;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceOrderChecklistController extends Controller
{
    public const DEFAULT_COMPONENTS = [
        'identificacion' => 'Identificación / Placa / Barcode',
        'cilindro' => 'Estado del Cilindro / Tanque',
        'corrosion' => 'Corrosión / Pintura',
        'golpes_deformacion' => 'Golpes / Deformación estructural',
        'valvula' => 'Válvula de descarga',
        'manometro' => 'Manómetro de presión',
        'pasador' => 'Pasador / Anillo de seguridad',
        'precinto' => 'Precinto de seguridad',
        'manguera' => 'Manguera de descarga',
        'boquilla_difusor' => 'Boquilla / Difusor / Corneta',
        'manija_palanca' => 'Manija de transporte / Palanca de accionamiento',
        'rotulado' => 'Etiqueta / Rotulado de instrucciones',
        'agente_carga' => 'Agente extintor / Carga',
        'servicio' => 'Estado general de atención técnica',
    ];

    /**
     * Display or initialize the technical checklist for an equipment in a service order.
     */
    public function show(ServiceOrder $serviceOrder, Equipment $equipment): Response
    {
        $this->authorize('viewAny', ServiceOrderChecklist::class);

        if (! $serviceOrder->equipment()->where('equipment.id', $equipment->id)->exists()) {
            abort(404, 'El equipo no pertenece a la orden de servicio indicada.');
        }

        $checklist = ServiceOrderChecklist::firstOrCreate(
            ['service_order_id' => $serviceOrder->id, 'equipment_id' => $equipment->id],
            ['estado' => 'borrador']
        );

        if ($checklist->items()->count() === 0) {
            foreach (self::DEFAULT_COMPONENTS as $key => $label) {
                // If equipment has no manometer (e.g. CO2), manometer can default to no_aplica
                $condicion = ($key === 'manometro' && Str_contains_ci($equipment->agente ?? '', 'co2')) ? 'no_aplica' : 'conforme';

                $checklist->items()->create([
                    'componente' => $key,
                    'condicion' => $condicion,
                ]);
            }
        }

        $checklist->load(['items.deficiency', 'completadoPorUser']);
        $serviceOrder->load(['client', 'clientSite', 'vehicle']);

        $spareParts = CatalogItem::query()
            ->where('activo', true)
            ->whereIn('tipo', ['repuesto', 'producto'])
            ->orderBy('nombre')
            ->get();

        return Inertia::render('checklists/show', [
            'serviceOrder' => $serviceOrder,
            'equipment' => $equipment,
            'checklist' => $checklist,
            'componentLabels' => self::DEFAULT_COMPONENTS,
            'spareParts' => $spareParts,
        ]);
    }

    /**
     * Store or update checklist items and generate deficiencies for observed components.
     */
    public function store(StoreChecklistRequest $request, ServiceOrder $serviceOrder, Equipment $equipment): RedirectResponse
    {
        $this->authorize('fill', ServiceOrderChecklist::class);

        if (! $serviceOrder->equipment()->where('equipment.id', $equipment->id)->exists()) {
            abort(404, 'El equipo no pertenece a la orden de servicio indicada.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($serviceOrder, $equipment, $validated, $request) {
            $checklist = ServiceOrderChecklist::firstOrCreate(
                ['service_order_id' => $serviceOrder->id, 'equipment_id' => $equipment->id]
            );

            $checklist->update([
                'completado_por_user_id' => $request->user()->id,
                'completado_en' => now(),
                'estado' => $validated['estado'] ?? 'completado',
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = $checklist->items()->updateOrCreate(
                    ['componente' => $itemData['componente']],
                    [
                        'condicion' => $itemData['condicion'],
                        'nota' => $itemData['nota'] ?? null,
                        'accion_recomendada' => $itemData['accion_recomendada'] ?? null,
                    ]
                );

                if ($itemData['condicion'] === 'observado') {
                    // Create or update linked deficiency
                    Deficiency::updateOrCreate(
                        [
                            'service_order_id' => $serviceOrder->id,
                            'equipment_id' => $equipment->id,
                            'checklist_item_id' => $item->id,
                        ],
                        [
                            'componente' => $itemData['componente'],
                            'condicion' => 'observado',
                            'nota' => $itemData['nota'] ?? null,
                            'accion_recomendada' => $itemData['accion_recomendada'] ?? 'Revisar / Reemplazar componente',
                            'repuesto_sugerido' => $itemData['repuesto_sugerido'] ?? null,
                            'catalog_item_id' => $itemData['catalog_item_id'] ?? null,
                            'requiere_autorizacion' => $itemData['requiere_autorizacion'] ?? true,
                            'estado' => 'detectada',
                        ]
                    );
                }
            }
        });

        return to_route('service-orders.show', $serviceOrder->id)->with('status', 'Checklist técnico guardado correctamente.');
    }
}

/**
 * Helper to check case insensitive substring
 */
function Str_contains_ci(string $haystack, string $needle): bool
{
    return str_contains(strtolower($haystack), strtolower($needle));
}
