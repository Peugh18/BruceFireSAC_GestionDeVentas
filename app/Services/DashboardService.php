<?php

namespace App\Services;

use App\Models\ElectronicDocument;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\ServiceOrder;
use Illuminate\Support\Carbon;

/**
 * Builds the Gerente dashboard: real KPIs and chart data computed from
 * existing tables. Reuses ReportService for the two lists it already
 * exposes in the right shape (equipos por vencer, stock critico) instead
 * of re-querying the same thing twice.
 */
class DashboardService
{
    public function __construct(private readonly ReportService $reportService) {}

    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->toDateString();

        $equipos = $this->reportService->getEquipos();
        $inventario = $this->reportService->getInventario($monthStart, $monthEnd);

        return [
            'kpis' => [
                'ventas_mes' => (float) Sale::query()
                    ->where('estado', '!=', 'anulada')
                    ->whereDate('fecha', '>=', $monthStart)
                    ->whereDate('fecha', '<=', $monthEnd)
                    ->sum('total'),
                'facturacion_mes' => (float) ElectronicDocument::query()
                    ->join('sales', 'sales.id', '=', 'electronic_documents.sale_id')
                    ->where('electronic_documents.estado', 'aceptado')
                    ->whereDate('electronic_documents.fecha_envio', '>=', $monthStart)
                    ->whereDate('electronic_documents.fecha_envio', '<=', $monthEnd)
                    ->sum('sales.total'),
                'por_cobrar' => (float) SaleInstallment::query()
                    ->where('monto_pendiente', '>', 0)
                    ->sum('monto_pendiente'),
                'servicios_pendientes' => ServiceOrder::query()
                    ->where('estado', '!=', 'cerrado')
                    ->count(),
                'equipos_por_vencer' => $equipos['resumen']['total_proximos_o_vencidos'],
            ],
            'ventas_mensuales' => $this->ventasMensuales(),
            'servicios_por_tipo' => $this->serviciosPorTipo(),
            'proximos_vencimientos' => $equipos['proximos_atencion']->take(8)->values(),
            'ordenes_recientes' => $this->ordenesRecientes(),
            'stock_critico' => $inventario['bajo_minimo']->take(8)->values(),
        ];
    }

    /**
     * Sales total per month for the last 6 months (including the current one).
     *
     * @return list<array{mes: string, label: string, total: float}>
     */
    private function ventasMensuales(): array
    {
        $start = now()->subMonths(5)->startOfMonth();

        // Grouped in PHP (not SQL) to stay database-agnostic: this dataset
        // is small (a few months of sales) so there is no performance cost.
        $rows = Sale::query()
            ->where('estado', '!=', 'anulada')
            ->where('fecha', '>=', $start->toDateString())
            ->get(['fecha', 'total'])
            ->groupBy(fn (Sale $sale) => $sale->fecha->format('Y-m'))
            ->map(fn ($sales) => $sales->sum('total'));

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $months[] = [
                'mes' => $key,
                'label' => Carbon::createFromDate((int) $date->format('Y'), (int) $date->format('m'), 1)->translatedFormat('M Y'),
                'total' => (float) ($rows[$key] ?? 0),
            ];
        }

        return $months;
    }

    /**
     * @return list<array{tipo: string, label: string, total: int}>
     */
    private function serviciosPorTipo(): array
    {
        return ServiceOrder::query()
            ->selectRaw('tipo_servicio, COUNT(*) as total')
            ->groupBy('tipo_servicio')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'tipo' => $row->tipo_servicio,
                'label' => ServiceOrder::SERVICE_TYPES[$row->tipo_servicio] ?? $row->tipo_servicio,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, codigo: string, cliente: string, estado: string, estado_label: string, fecha: string}>
     */
    private function ordenesRecientes(): array
    {
        return ServiceOrder::query()
            ->with('client:id,razon_social')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (ServiceOrder $order) => [
                'id' => $order->id,
                'codigo' => $order->codigo,
                'cliente' => $order->client?->razon_social ?? '-',
                'estado' => $order->estado,
                'estado_label' => ServiceOrder::STATUS_LABELS[$order->estado] ?? $order->estado,
                'fecha' => $order->fecha->toDateString(),
            ])
            ->all();
    }
}
