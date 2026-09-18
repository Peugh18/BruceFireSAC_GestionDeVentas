<?php

namespace App\Services\AI;

use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * "Asistente gerencial" (S39.3): answers natural-language questions about
 * data that already exists in the system. Never predicts, never invents
 * figures, never performs actions (S39.5) — it only summarizes a bounded,
 * permission-checked snapshot of real data and asks Gemini to phrase an
 * answer strictly from that snapshot.
 */
class GeminiAssistantService
{
    public function __construct(private readonly ReportService $reportService) {}

    public function ask(User $user, string $question): string
    {
        // Aggregate, business-wide figures are gated behind reports.view,
        // same boundary the Reports module itself uses. Without it, the
        // assistant has nothing safe to summarize.
        if (! $user->can('reports.view')) {
            return 'No tienes permiso para consultar datos agregados del negocio (se requiere el permiso de reportes).';
        }

        $context = $this->buildContext();

        return $this->askGemini($question, $context);
    }

    /**
     * A bounded, plain-language snapshot of real data — never a raw DB dump
     * and never the whole database. Reuses ReportService so this stays in
     * sync with the numbers shown in Reportes/Dashboard instead of
     * recomputing them differently.
     */
    private function buildContext(): string
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->toDateString();
        $prevMonthStart = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $prevMonthEnd = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $comerciales = $this->reportService->getComerciales($monthStart, $monthEnd);
        $equipos = $this->reportService->getEquipos();
        $inventario = $this->reportService->getInventario($monthStart, $monthEnd);

        $clientesConEquiposPorVencer = collect($equipos['proximos_atencion'])
            ->groupBy('cliente')
            ->map->count()
            ->sortDesc()
            ->take(5);

        $servicioQueMasCrecio = $this->servicioConMayorCrecimiento($monthStart, $monthEnd, $prevMonthStart, $prevMonthEnd);

        $repuestosPorAgotarse = collect($inventario['bajo_minimo'])
            ->take(5)
            ->map(fn ($item) => "{$item->nombre} (quedan {$item->stock_actual} de {$item->stock_minimo} minimo)")
            ->implode('; ');

        $lines = [
            "Periodo del mes actual: {$monthStart} a {$monthEnd}.",
            "Ventas del mes: {$comerciales['resumen']['total_transacciones']} ventas por un total de S/ ".number_format($comerciales['resumen']['total_vendido'], 2).'.',
            'Cotizaciones del mes: '.$comerciales['resumen']['total_cotizaciones']." emitidas, {$comerciales['resumen']['cotizaciones_aceptadas']} aceptadas (tasa de conversion {$comerciales['resumen']['tasa_conversion']}%).",
            'Equipos proximos a vencer o vencidos (30 dias): '.$equipos['resumen']['total_proximos_o_vencidos'].' en total.',
            'Clientes con mas equipos proximos a vencer: '.($clientesConEquiposPorVencer->isEmpty()
                ? 'ninguno.'
                : $clientesConEquiposPorVencer->map(fn ($total, $cliente) => "{$cliente} ({$total})")->implode('; ').'.'),
            "Servicio que mas crecio este mes vs el mes anterior: {$servicioQueMasCrecio}",
            'Repuestos cerca de agotarse: '.($repuestosPorAgotarse !== '' ? $repuestosPorAgotarse.'.' : 'ninguno bajo el minimo.'),
        ];

        return implode("\n", $lines);
    }

    private function servicioConMayorCrecimiento(string $monthStart, string $monthEnd, string $prevMonthStart, string $prevMonthEnd): string
    {
        $actual = ServiceOrder::query()
            ->whereBetween('fecha', [$monthStart, $monthEnd])
            ->selectRaw('tipo_servicio, COUNT(*) as total')
            ->groupBy('tipo_servicio')
            ->pluck('total', 'tipo_servicio');

        $anterior = ServiceOrder::query()
            ->whereBetween('fecha', [$prevMonthStart, $prevMonthEnd])
            ->selectRaw('tipo_servicio, COUNT(*) as total')
            ->groupBy('tipo_servicio')
            ->pluck('total', 'tipo_servicio');

        $mejorTipo = null;
        $mejorDelta = null;

        foreach ($actual as $tipo => $total) {
            $delta = $total - ($anterior[$tipo] ?? 0);
            if ($mejorDelta === null || $delta > $mejorDelta) {
                $mejorDelta = $delta;
                $mejorTipo = $tipo;
            }
        }

        if ($mejorTipo === null) {
            return 'sin ordenes de servicio registradas este mes.';
        }

        $label = ServiceOrder::SERVICE_TYPES[$mejorTipo] ?? $mejorTipo;

        return "{$label} ({$actual[$mejorTipo]} este mes vs ".($anterior[$mejorTipo] ?? 0).' el mes anterior).';
    }

    private function askGemini(string $question, string $context): string
    {
        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            return 'El asistente no esta configurado (falta GEMINI_API_KEY).';
        }

        $systemPrompt = 'Eres el asistente gerencial interno de BRUCE FIRE S.A.C., una empresa de mantenimiento de extintores. '
            .'Responde EXCLUSIVAMENTE usando los datos que se te entregan a continuacion. '
            .'Si la pregunta no puede responderse con esos datos, dilo claramente en vez de inventar cifras, series, anios o datos tributarios. '
            .'Nunca autorizas descuentos, nunca emites comprobantes de pago ni guias, y nunca modificas inventario: solo informas. '
            .'Responde en espanol, de forma breve y directa, sin markdown.';

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-goog-api-key' => $apiKey,
                ])
                ->post(
                    config('services.gemini.endpoint').'/'.config('services.gemini.model').':generateContent',
                    [
                        'systemInstruction' => [
                            'parts' => [['text' => $systemPrompt]],
                        ],
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [['text' => "Datos disponibles:\n{$context}\n\nPregunta: {$question}"]],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.2,
                            'maxOutputTokens' => 500,
                        ],
                    ]
                );
        } catch (\Throwable $e) {
            Log::warning('Gemini assistant request failed', ['error' => $e->getMessage()]);

            return 'No se pudo conectar con el asistente en este momento. Intenta nuevamente.';
        }

        if ($response->failed()) {
            Log::warning('Gemini assistant returned an error', ['status' => $response->status(), 'body' => $response->body()]);

            return 'No se pudo conectar con el asistente en este momento. Intenta nuevamente.';
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        return is_string($text) && trim($text) !== ''
            ? trim($text)
            : 'No se pudo generar una respuesta a partir de los datos disponibles.';
    }
}
