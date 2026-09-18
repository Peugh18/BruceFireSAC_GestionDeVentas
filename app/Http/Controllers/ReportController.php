<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Render the reports dashboard.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $categoria = $request->string('categoria', 'comerciales')->toString();
        $from = $request->string('fecha_desde', now()->startOfMonth()->toDateString())->toString();
        $to = $request->string('fecha_hasta', now()->toDateString())->toString();

        $data = match ($categoria) {
            'inventario' => $this->reportService->getInventario($from, $to),
            'servicios' => $this->reportService->getServicios($from, $to),
            'equipos' => $this->reportService->getEquipos(),
            'certificados' => $this->reportService->getCertificados($from, $to),
            'facturacion' => $this->reportService->getFacturacion($from, $to),
            'cobranzas' => $this->reportService->getCobranzas($from, $to),
            default => $this->reportService->getComerciales($from, $to),
        };

        return Inertia::render('reports/index', [
            'categoria' => $categoria,
            'filters' => [
                'fecha_desde' => $from,
                'fecha_hasta' => $to,
            ],
            'reportData' => $data,
        ]);
    }

    /**
     * Export selected subreport as CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $categoria = $request->string('categoria', 'comerciales')->toString();
        $subreporte = $request->string('subreporte', 'general')->toString();
        $from = $request->string('fecha_desde', now()->startOfMonth()->toDateString())->toString();
        $to = $request->string('fecha_hasta', now()->toDateString())->toString();

        return $this->reportService->exportCsv($categoria, $subreporte, $from, $to);
    }
}
