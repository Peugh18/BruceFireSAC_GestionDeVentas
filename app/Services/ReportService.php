<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Deficiency;
use App\Models\ElectronicDocument;
use App\Models\Equipment;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderStatusHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Reportes Comerciales
     *
     * @return array<string, mixed>
     */
    public function getComerciales(string $from, string $to): array
    {
        // 1. Ventas por periodo
        $ventasPeriodo = Sale::query()
            ->where('estado', '!=', 'anulada')
            ->whereDate('fecha', '>=', $from)
            ->whereDate('fecha', '<=', $to)
            ->selectRaw('fecha, COUNT(*) as total_ventas, SUM(subtotal) as subtotal, SUM(igv) as igv, SUM(total) as monto_total')
            ->groupBy('fecha')
            ->orderBy('fecha', 'desc')
            ->get();

        // 2. Ventas por vendedor
        $ventasVendedor = Sale::query()
            ->join('users', 'sales.vendedor_user_id', '=', 'users.id')
            ->where('sales.estado', '!=', 'anulada')
            ->whereDate('sales.fecha', '>=', $from)
            ->whereDate('sales.fecha', '<=', $to)
            ->selectRaw('users.id, users.name as vendedor, COUNT(*) as total_ventas, SUM(sales.total) as monto_total')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('monto_total')
            ->get();

        // 3. Ventas por cliente
        $ventasCliente = Sale::query()
            ->join('clients', 'sales.client_id', '=', 'clients.id')
            ->where('sales.estado', '!=', 'anulada')
            ->whereDate('sales.fecha', '>=', $from)
            ->whereDate('sales.fecha', '<=', $to)
            ->selectRaw('clients.id, clients.razon_social as cliente, clients.numero_documento, COUNT(*) as total_ventas, SUM(sales.total) as monto_total')
            ->groupBy('clients.id', 'clients.razon_social', 'clients.numero_documento')
            ->orderByDesc('monto_total')
            ->limit(20)
            ->get();

        // 4. Ventas por producto / servicio
        $ventasProducto = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('catalog_items', 'sale_items.catalog_item_id', '=', 'catalog_items.id')
            ->where('sales.estado', '!=', 'anulada')
            ->whereDate('sales.fecha', '>=', $from)
            ->whereDate('sales.fecha', '<=', $to)
            ->selectRaw('catalog_items.id, catalog_items.codigo, catalog_items.nombre, catalog_items.tipo, SUM(sale_items.cantidad) as cantidad_vendida, SUM(sale_items.subtotal) as monto_total')
            ->groupBy('catalog_items.id', 'catalog_items.codigo', 'catalog_items.nombre', 'catalog_items.tipo')
            ->orderByDesc('monto_total')
            ->limit(20)
            ->get();

        // 5. Conversión de cotizaciones
        $quotesTotal = Quote::whereDate('fecha', '>=', $from)->whereDate('fecha', '<=', $to)->count();
        $quotesAceptadas = Quote::whereDate('fecha', '>=', $from)->whereDate('fecha', '<=', $to)->whereIn('estado', ['aceptada', 'convertida'])->count();
        $quotesRechazadas = Quote::whereDate('fecha', '>=', $from)->whereDate('fecha', '<=', $to)->where('estado', 'rechazada')->count();
        $quotesPendientes = Quote::whereDate('fecha', '>=', $from)->whereDate('fecha', '<=', $to)->whereIn('estado', ['borrador', 'emitida', 'enviada'])->count();
        $tasaConversion = $quotesTotal > 0 ? round(($quotesAceptadas / $quotesTotal) * 100, 1) : 0;

        $totalVendido = (float) $ventasPeriodo->sum('monto_total');
        $totalTransacciones = (int) $ventasPeriodo->sum('total_ventas');

        return [
            'resumen' => [
                'total_vendido' => $totalVendido,
                'total_transacciones' => $totalTransacciones,
                'total_cotizaciones' => $quotesTotal,
                'cotizaciones_aceptadas' => $quotesAceptadas,
                'cotizaciones_rechazadas' => $quotesRechazadas,
                'cotizaciones_pendientes' => $quotesPendientes,
                'tasa_conversion' => $tasaConversion,
            ],
            'ventas_periodo' => $ventasPeriodo,
            'ventas_vendedor' => $ventasVendedor,
            'ventas_cliente' => $ventasCliente,
            'ventas_producto' => $ventasProducto,
        ];
    }

    /**
     * Reportes de Inventario
     *
     * @return array<string, mixed>
     */
    public function getInventario(?string $from = null, ?string $to = null): array
    {
        // 1. Stock actual
        $stockActual = InventoryStock::query()
            ->join('catalog_items', 'inventory_stocks.catalog_item_id', '=', 'catalog_items.id')
            ->selectRaw('inventory_stocks.id, catalog_items.codigo, catalog_items.nombre, catalog_items.tipo, catalog_items.categoria, catalog_items.unidad, inventory_stocks.stock_actual, inventory_stocks.stock_minimo, CASE WHEN inventory_stocks.stock_actual <= inventory_stocks.stock_minimo THEN 1 ELSE 0 END as bajo_minimo')
            ->orderBy('catalog_items.nombre')
            ->get();

        // 2. Movimientos por periodo
        $movimientos = InventoryMovement::query()
            ->with(['catalogItem:id,codigo,nombre,unidad', 'usuario:id,name'])
            ->when($from && $to, fn ($q) => $q->whereBetween('fecha', [$from, $to]))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        // 3. Repuestos / artículos con más rotación (salidas)
        $rotacion = InventoryMovement::query()
            ->join('catalog_items', 'inventory_movements.catalog_item_id', '=', 'catalog_items.id')
            ->where('inventory_movements.tipo', 'salida')
            ->when($from && $to, fn ($q) => $q->whereBetween('inventory_movements.fecha', [$from, $to]))
            ->selectRaw('catalog_items.id, catalog_items.codigo, catalog_items.nombre, catalog_items.tipo, catalog_items.unidad, SUM(inventory_movements.cantidad) as total_salidas, COUNT(*) as num_movimientos')
            ->groupBy('catalog_items.id', 'catalog_items.codigo', 'catalog_items.nombre', 'catalog_items.tipo', 'catalog_items.unidad')
            ->orderByDesc('total_salidas')
            ->limit(15)
            ->get();

        // 4. Items bajo stock mínimo
        $bajoMinimo = InventoryStock::query()
            ->join('catalog_items', 'inventory_stocks.catalog_item_id', '=', 'catalog_items.id')
            ->whereColumn('inventory_stocks.stock_actual', '<=', 'inventory_stocks.stock_minimo')
            ->where('catalog_items.controla_stock', true)
            ->selectRaw('catalog_items.codigo, catalog_items.nombre, catalog_items.categoria, catalog_items.unidad, inventory_stocks.stock_actual, inventory_stocks.stock_minimo, (inventory_stocks.stock_minimo - inventory_stocks.stock_actual) as faltante')
            ->orderByDesc('faltante')
            ->get();

        return [
            'resumen' => [
                'total_items' => $stockActual->count(),
                'total_bajo_minimo' => $bajoMinimo->count(),
                'total_movimientos' => $movimientos->count(),
            ],
            'stock_actual' => $stockActual,
            'movimientos' => $movimientos,
            'rotacion' => $rotacion,
            'bajo_minimo' => $bajoMinimo,
        ];
    }

    /**
     * Reportes de Servicios
     *
     * @return array<string, mixed>
     */
    public function getServicios(string $from, string $to): array
    {
        // 1. Órdenes por estado
        $ordenesPorEstado = ServiceOrder::query()
            ->whereBetween('fecha', [$from, $to])
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get();

        // 2. Tiempo promedio por estado desde ServiceOrderStatusHistory
        $histories = ServiceOrderStatusHistory::query()
            ->join('service_orders', 'service_order_status_histories.service_order_id', '=', 'service_orders.id')
            ->whereBetween('service_orders.fecha', [$from, $to])
            ->select(['service_order_status_histories.service_order_id', 'service_order_status_histories.estado_anterior', 'service_order_status_histories.estado', 'service_order_status_histories.created_at'])
            ->orderBy('service_order_status_histories.service_order_id')
            ->orderBy('service_order_status_histories.created_at')
            ->get()
            ->groupBy('service_order_id');

        $tiemposPorEstado = [];
        foreach ($histories as $orderHistories) {
            $prev = null;
            foreach ($orderHistories as $entry) {
                if ($prev !== null && $prev->estado) {
                    $diffHours = Carbon::parse($prev->created_at)->diffInHours(Carbon::parse($entry->created_at));
                    $tiemposPorEstado[$prev->estado][] = $diffHours;
                }
                $prev = $entry;
            }
        }

        $promediosEstado = [];
        foreach ($tiemposPorEstado as $st => $hoursList) {
            $avg = count($hoursList) > 0 ? round(array_sum($hoursList) / count($hoursList), 1) : 0;
            $promediosEstado[] = [
                'estado' => $st,
                'estado_nombre' => ServiceOrder::STATUS_LABELS[$st] ?? $st,
                'promedio_horas' => $avg,
                'transiciones_analizadas' => count($hoursList),
            ];
        }

        // 3. Deficiencias por tipo / componente
        $deficiencias = Deficiency::query()
            ->whereBetween('created_at', ["$from 00:00:00", "$to 23:59:59"])
            ->selectRaw('componente, COUNT(*) as total, SUM(CASE WHEN estado = "resuelta" THEN 1 ELSE 0 END) as resueltas, SUM(CASE WHEN estado = "autorizada" THEN 1 ELSE 0 END) as autorizadas, SUM(CASE WHEN estado = "detectada" THEN 1 ELSE 0 END) as detectadas')
            ->groupBy('componente')
            ->orderByDesc('total')
            ->get();

        return [
            'resumen' => [
                'total_ordenes' => $ordenesPorEstado->sum('total'),
                'total_deficiencias' => $deficiencias->sum('total'),
            ],
            'ordenes_por_estado' => $ordenesPorEstado->map(fn ($row) => [
                'estado' => $row->estado,
                'label' => ServiceOrder::STATUS_LABELS[$row->estado] ?? $row->estado,
                'total' => $row->total,
            ]),
            'tiempo_promedio_estado' => $promediosEstado,
            'deficiencias_por_tipo' => $deficiencias,
        ];
    }

    /**
     * Reportes de Equipos
     *
     * @return array<string, mixed>
     */
    public function getEquipos(): array
    {
        $today = now()->toDateString();
        $in30Days = now()->addDays(30)->toDateString();

        // 1. Próximos a atención / PH
        $proximos = Equipment::query()
            ->with('client:id,razon_social')
            ->where(function ($q) use ($in30Days) {
                $q->whereNotNull('proxima_atencion')
                    ->where('proxima_atencion', '<=', $in30Days);
            })
            ->orWhere(function ($q) use ($in30Days) {
                $q->whereNotNull('proxima_ph')
                    ->where('proxima_ph', '<=', $in30Days);
            })
            ->select(['id', 'codigo', 'client_id', 'tipo_equipo', 'ubicacion', 'proxima_atencion', 'proxima_ph', 'estado'])
            ->orderBy('proxima_atencion')
            ->limit(50)
            ->get()
            ->map(function ($eq) use ($today) {
                $atencionVencida = $eq->proxima_atencion && $eq->proxima_atencion->toDateString() < $today;
                $phVencida = $eq->proxima_ph && $eq->proxima_ph->toDateString() < $today;

                return [
                    'id' => $eq->id,
                    'codigo' => $eq->codigo,
                    'cliente' => $eq->client?->razon_social ?? '-',
                    'tipo_equipo' => $eq->tipo_equipo,
                    'ubicacion' => $eq->ubicacion ?? '-',
                    'proxima_atencion' => $eq->proxima_atencion?->toDateString(),
                    'proxima_ph' => $eq->proxima_ph?->toDateString(),
                    'atencion_vencida' => $atencionVencida,
                    'ph_vencida' => $phVencida,
                    'estado' => $eq->estado,
                ];
            });

        // 2. Equipos por estado
        $porEstado = Equipment::query()
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get();

        // 3. Equipos por cliente
        $porCliente = Equipment::query()
            ->join('clients', 'equipment.client_id', '=', 'clients.id')
            ->selectRaw('clients.id, clients.razon_social as cliente, COUNT(*) as total_equipos')
            ->groupBy('clients.id', 'clients.razon_social')
            ->orderByDesc('total_equipos')
            ->limit(20)
            ->get();

        return [
            'resumen' => [
                'total_equipos' => $porEstado->sum('total'),
                'total_proximos_o_vencidos' => $proximos->count(),
            ],
            'proximos_atencion' => $proximos,
            'equipos_por_estado' => $porEstado,
            'equipos_por_cliente' => $porCliente,
        ];
    }

    /**
     * Reportes de Certificados
     *
     * @return array<string, mixed>
     */
    public function getCertificados(string $from, string $to): array
    {
        // 1. Por estado
        $porEstado = Certificate::query()
            ->whereBetween('fecha_emision', [$from, $to])
            ->selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->get()
            ->map(fn ($row) => [
                'estado' => $row->estado,
                'label' => Certificate::ESTADO_LABELS[$row->estado] ?? $row->estado,
                'total' => $row->total,
            ]);

        // 2. Por tipo
        $porTipo = Certificate::query()
            ->whereBetween('fecha_emision', [$from, $to])
            ->selectRaw('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->get()
            ->map(fn ($row) => [
                'tipo' => $row->tipo,
                'label' => Certificate::TIPO_LABELS[$row->tipo] ?? $row->tipo,
                'total' => $row->total,
            ]);

        // 3. Listado de certificados emitidos
        $certificados = Certificate::query()
            ->with(['serviceOrder:id,codigo,client_id', 'serviceOrder.client:id,razon_social'])
            ->whereBetween('fecha_emision', [$from, $to])
            ->orderByDesc('fecha_emision')
            ->limit(50)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'numero' => $c->numero,
                'tipo' => Certificate::TIPO_LABELS[$c->tipo] ?? $c->tipo,
                'cliente' => $c->serviceOrder?->client?->razon_social ?? '-',
                'orden_codigo' => $c->serviceOrder?->codigo ?? '-',
                'fecha_emision' => $c->fecha_emision?->toDateString(),
                'fecha_vigencia' => $c->fecha_vigencia?->toDateString() ?? 'Sin vigencia',
                'estado' => Certificate::ESTADO_LABELS[$c->estado] ?? $c->estado,
            ]);

        return [
            'resumen' => [
                'total_certificados' => $porEstado->sum('total'),
            ],
            'por_estado' => $porEstado,
            'por_tipo' => $porTipo,
            'certificados' => $certificados,
        ];
    }

    /**
     * Reportes de Facturación
     *
     * @return array<string, mixed>
     */
    public function getFacturacion(string $from, string $to): array
    {
        // 1. Documentos electrónicos por tipo y estado
        $documentosPorEstado = ElectronicDocument::query()
            ->whereBetween('created_at', ["$from 00:00:00", "$to 23:59:59"])
            ->selectRaw('tipo, estado, COUNT(*) as total')
            ->groupBy('tipo', 'estado')
            ->get()
            ->map(fn ($row) => [
                'tipo' => ElectronicDocument::TIPO_LABELS[$row->tipo] ?? $row->tipo,
                'estado' => ElectronicDocument::ESTADO_LABELS[$row->estado] ?? $row->estado,
                'total' => $row->total,
            ]);

        // 2. Errores SUNAT
        $erroresSunat = ElectronicDocument::query()
            ->where(function ($q) {
                $q->whereIn('estado', ['rechazado', 'error'])
                    ->orWhereNotNull('error');
            })
            ->whereBetween('created_at', ["$from 00:00:00", "$to 23:59:59"])
            ->select(['id', 'sale_id', 'tipo', 'serie', 'correlativo', 'estado', 'error', 'respuesta_sunat', 'intentos', 'fecha_envio'])
            ->orderByDesc('id')
            ->get()
            ->map(fn ($doc) => [
                'id' => $doc->id,
                'documento' => $doc->numeroCompleto(),
                'tipo' => ElectronicDocument::TIPO_LABELS[$doc->tipo] ?? $doc->tipo,
                'estado' => ElectronicDocument::ESTADO_LABELS[$doc->estado] ?? $doc->estado,
                'error' => $doc->error ?? 'Error de validación',
                'respuesta_sunat' => $doc->respuesta_sunat,
                'intentos' => $doc->intentos,
                'fecha_envio' => $doc->fecha_envio ? $doc->fecha_envio->toDateTimeString() : '-',
            ]);

        // 3. Ventas contado vs crédito
        $contadoCredito = Sale::query()
            ->where('estado', '!=', 'anulada')
            ->whereBetween('fecha', [$from, $to])
            ->selectRaw('condicion_pago, COUNT(*) as total_ventas, SUM(total) as monto_total')
            ->groupBy('condicion_pago')
            ->get();

        return [
            'resumen' => [
                'total_documentos' => $documentosPorEstado->sum('total'),
                'total_errores' => $erroresSunat->count(),
                'total_ventas_monto' => $contadoCredito->sum('monto_total'),
            ],
            'documentos_por_estado' => $documentosPorEstado,
            'errores_sunat' => $erroresSunat,
            'ventas_contado_credito' => $contadoCredito,
        ];
    }

    /**
     * Reportes de Cobranzas
     *
     * @return array<string, mixed>
     */
    public function getCobranzas(string $from, string $to): array
    {
        $today = now()->toDateString();

        // 1. Totales de saldos y vencidos
        $totalPorCobrar = SaleInstallment::query()
            ->where('monto_pendiente', '>', 0)
            ->sum('monto_pendiente');

        $totalVencido = SaleInstallment::query()
            ->where('monto_pendiente', '>', 0)
            ->where('fecha_vencimiento', '<', $today)
            ->sum('monto_pendiente');

        // 2. Cuotas pendientes
        $cuotasPendientes = SaleInstallment::query()
            ->with(['sale:id,numero,client_id', 'sale.client:id,razon_social'])
            ->where('monto_pendiente', '>', 0)
            ->orderBy('fecha_vencimiento')
            ->limit(50)
            ->get()
            ->map(fn ($cuota) => [
                'id' => $cuota->id,
                'venta_numero' => $cuota->sale?->numero ?? '-',
                'cliente' => $cuota->sale?->client?->razon_social ?? '-',
                'numero_cuota' => $cuota->numero_cuota,
                'monto' => $cuota->monto,
                'monto_pendiente' => $cuota->monto_pendiente,
                'fecha_vencimiento' => $cuota->fecha_vencimiento?->toDateString(),
                'vencida' => $cuota->fecha_vencimiento && $cuota->fecha_vencimiento->toDateString() < $today,
                'estado' => $cuota->estado,
            ]);

        // 3. Cobrado en periodo
        $totalCobrado = SalePayment::query()
            ->whereBetween(DB::raw('COALESCE(fecha, DATE(created_at))'), [$from, $to])
            ->sum('monto');

        $cobradoPorMedio = SalePayment::query()
            ->whereBetween(DB::raw('COALESCE(fecha, DATE(created_at))'), [$from, $to])
            ->selectRaw('forma_pago, COUNT(*) as total_operaciones, SUM(monto) as total_monto')
            ->groupBy('forma_pago')
            ->orderByDesc('total_monto')
            ->get();

        return [
            'resumen' => [
                'total_por_cobrar' => (float) $totalPorCobrar,
                'total_vencido' => (float) $totalVencido,
                'total_cobrado_periodo' => (float) $totalCobrado,
            ],
            'cuotas_pendientes' => $cuotasPendientes,
            'cobrado_por_medio' => $cobradoPorMedio,
        ];
    }

    /**
     * Exportación CSV con BOM UTF-8 para Excel
     */
    public function exportCsv(string $categoria, string $subreporte, string $from, string $to): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"reporte_{$categoria}_{$subreporte}_{$from}_{$to}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($categoria, $subreporte, $from, $to) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens it with proper encoding
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            switch ($categoria) {
                case 'comerciales':
                    $this->exportComercialesCsv($handle, $subreporte, $from, $to);
                    break;
                case 'inventario':
                    $this->exportInventarioCsv($handle, $subreporte, $from, $to);
                    break;
                case 'servicios':
                    $this->exportServiciosCsv($handle, $subreporte, $from, $to);
                    break;
                case 'equipos':
                    $this->exportEquiposCsv($handle, $subreporte);
                    break;
                case 'certificados':
                    $this->exportCertificadosCsv($handle, $subreporte, $from, $to);
                    break;
                case 'facturacion':
                    $this->exportFacturacionCsv($handle, $subreporte, $from, $to);
                    break;
                case 'cobranzas':
                    $this->exportCobranzasCsv($handle, $subreporte, $from, $to);
                    break;
                default:
                    fputcsv($handle, ['Reporte no disponible']);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * @param  resource  $handle
     */
    private function exportComercialesCsv($handle, string $subreporte, string $from, string $to): void
    {
        $data = $this->getComerciales($from, $to);

        if ($subreporte === 'vendedor') {
            fputcsv($handle, ['Vendedor', 'Total Ventas', 'Monto Total']);
            foreach ($data['ventas_vendedor'] as $row) {
                fputcsv($handle, [$row->vendedor, $row->total_ventas, number_format((float) $row->monto_total, 2, '.', '')]);
            }
        } elseif ($subreporte === 'cliente') {
            fputcsv($handle, ['Cliente', 'RUC/DNI', 'Total Ventas', 'Monto Total']);
            foreach ($data['ventas_cliente'] as $row) {
                fputcsv($handle, [$row->cliente, $row->numero_documento, $row->total_ventas, number_format((float) $row->monto_total, 2, '.', '')]);
            }
        } elseif ($subreporte === 'producto') {
            fputcsv($handle, ['Codigo', 'Nombre', 'Tipo', 'Cantidad Vendida', 'Monto Total']);
            foreach ($data['ventas_producto'] as $row) {
                fputcsv($handle, [$row->codigo, $row->nombre, $row->tipo, $row->cantidad_vendida, number_format((float) $row->monto_total, 2, '.', '')]);
            }
        } else {
            fputcsv($handle, ['Fecha', 'Total Ventas', 'Subtotal', 'IGV', 'Monto Total']);
            foreach ($data['ventas_periodo'] as $row) {
                fputcsv($handle, [
                    $row->fecha,
                    $row->total_ventas,
                    number_format((float) $row->subtotal, 2, '.', ''),
                    number_format((float) $row->igv, 2, '.', ''),
                    number_format((float) $row->monto_total, 2, '.', ''),
                ]);
            }
        }
    }

    /**
     * @param  resource  $handle
     */
    private function exportInventarioCsv($handle, string $subreporte, string $from, string $to): void
    {
        $data = $this->getInventario($from, $to);

        if ($subreporte === 'movimientos') {
            fputcsv($handle, ['Fecha', 'Codigo', 'Articulo', 'Tipo Movimiento', 'Cantidad', 'Stock Antes', 'Stock Despues', 'Motivo', 'Usuario']);
            foreach ($data['movimientos'] as $m) {
                fputcsv($handle, [
                    $m->fecha,
                    $m->catalogItem?->codigo ?? '-',
                    $m->catalogItem?->nombre ?? '-',
                    $m->tipo,
                    $m->cantidad,
                    $m->stock_antes,
                    $m->stock_despues,
                    $m->motivo,
                    $m->usuario?->name ?? '-',
                ]);
            }
        } elseif ($subreporte === 'rotacion') {
            fputcsv($handle, ['Codigo', 'Articulo', 'Tipo', 'Unidad', 'Total Salidas', 'Movimientos']);
            foreach ($data['rotacion'] as $r) {
                fputcsv($handle, [$r->codigo, $r->nombre, $r->tipo, $r->unidad, $r->total_salidas, $r->num_movimientos]);
            }
        } elseif ($subreporte === 'bajo_minimo') {
            fputcsv($handle, ['Codigo', 'Articulo', 'Categoria', 'Unidad', 'Stock Actual', 'Stock Minimo', 'Faltante']);
            foreach ($data['bajo_minimo'] as $b) {
                fputcsv($handle, [$b->codigo, $b->nombre, $b->categoria, $b->unidad, $b->stock_actual, $b->stock_minimo, $b->faltante]);
            }
        } else {
            fputcsv($handle, ['Codigo', 'Articulo', 'Tipo', 'Categoria', 'Unidad', 'Stock Actual', 'Stock Minimo', 'Alerta']);
            foreach ($data['stock_actual'] as $s) {
                fputcsv($handle, [$s->codigo, $s->nombre, $s->tipo, $s->categoria, $s->unidad, $s->stock_actual, $s->stock_minimo, $s->bajo_minimo ? 'Bajo Minimo' : 'Normal']);
            }
        }
    }

    /**
     * @param  resource  $handle
     */
    private function exportServiciosCsv($handle, string $subreporte, string $from, string $to): void
    {
        $data = $this->getServicios($from, $to);

        if ($subreporte === 'tiempos') {
            fputcsv($handle, ['Estado', 'Nombre de Estado', 'Promedio Horas', 'Transiciones']);
            foreach ($data['tiempo_promedio_estado'] as $t) {
                fputcsv($handle, [$t['estado'], $t['estado_nombre'], $t['promedio_horas'], $t['transiciones_analizadas']]);
            }
        } elseif ($subreporte === 'deficiencias') {
            fputcsv($handle, ['Componente', 'Total Deficiencias', 'Detectadas', 'Autorizadas', 'Resueltas']);
            foreach ($data['deficiencias_por_tipo'] as $d) {
                fputcsv($handle, [$d->componente, $d->total, $d->detectadas, $d->autorizadas, $d->resueltas]);
            }
        } else {
            fputcsv($handle, ['Estado', 'Etiqueta', 'Total Ordenes']);
            foreach ($data['ordenes_por_estado'] as $o) {
                fputcsv($handle, [$o['estado'], $o['label'], $o['total']]);
            }
        }
    }

    /**
     * @param  resource  $handle
     */
    private function exportEquiposCsv($handle, string $subreporte): void
    {
        $data = $this->getEquipos();

        if ($subreporte === 'cliente') {
            fputcsv($handle, ['Cliente', 'Total Equipos']);
            foreach ($data['equipos_por_cliente'] as $c) {
                fputcsv($handle, [$c->cliente, $c->total_equipos]);
            }
        } elseif ($subreporte === 'estado') {
            fputcsv($handle, ['Estado', 'Total']);
            foreach ($data['equipos_por_estado'] as $e) {
                fputcsv($handle, [$e->estado, $e->total]);
            }
        } else {
            fputcsv($handle, ['Codigo', 'Cliente', 'Tipo Equipo', 'Ubicacion', 'Proxima Atencion', 'Proxima PH', 'Estado']);
            foreach ($data['proximos_atencion'] as $p) {
                fputcsv($handle, [$p['codigo'], $p['cliente'], $p['tipo_equipo'], $p['ubicacion'], $p['proxima_atencion'] ?? '-', $p['proxima_ph'] ?? '-', $p['estado']]);
            }
        }
    }

    /**
     * @param  resource  $handle
     */
    private function exportCertificadosCsv($handle, string $subreporte, string $from, string $to): void
    {
        $data = $this->getCertificados($from, $to);

        if ($subreporte === 'estado') {
            fputcsv($handle, ['Estado', 'Total']);
            foreach ($data['por_estado'] as $e) {
                fputcsv($handle, [$e['label'], $e['total']]);
            }
        } elseif ($subreporte === 'tipo') {
            fputcsv($handle, ['Tipo Certificado', 'Total']);
            foreach ($data['por_tipo'] as $t) {
                fputcsv($handle, [$t['label'], $t['total']]);
            }
        } else {
            fputcsv($handle, ['Numero', 'Tipo', 'Cliente', 'Orden de Servicio', 'Fecha Emision', 'Fecha Vigencia', 'Estado']);
            foreach ($data['certificados'] as $c) {
                fputcsv($handle, [$c['numero'], $c['tipo'], $c['cliente'], $c['orden_codigo'], $c['fecha_emision'], $c['fecha_vigencia'], $c['estado']]);
            }
        }
    }

    /**
     * @param  resource  $handle
     */
    private function exportFacturacionCsv($handle, string $subreporte, string $from, string $to): void
    {
        $data = $this->getFacturacion($from, $to);

        if ($subreporte === 'errores') {
            fputcsv($handle, ['Documento', 'Tipo', 'Estado', 'Error', 'Respuesta SUNAT', 'Intentos', 'Fecha Envio']);
            foreach ($data['errores_sunat'] as $err) {
                fputcsv($handle, [$err['documento'], $err['tipo'], $err['estado'], $err['error'], $err['respuesta_sunat'] ?? '-', $err['intentos'], $err['fecha_envio']]);
            }
        } elseif ($subreporte === 'contado_credito') {
            fputcsv($handle, ['Condicion de Pago', 'Total Ventas', 'Monto Total']);
            foreach ($data['ventas_contado_credito'] as $v) {
                fputcsv($handle, [$v->condicion_pago, $v->total_ventas, number_format((float) $v->monto_total, 2, '.', '')]);
            }
        } else {
            fputcsv($handle, ['Tipo Documento', 'Estado SUNAT', 'Total Documentos']);
            foreach ($data['documentos_por_estado'] as $d) {
                fputcsv($handle, [$d['tipo'], $d['estado'], $d['total']]);
            }
        }
    }

    /**
     * @param  resource  $handle
     */
    private function exportCobranzasCsv($handle, string $subreporte, string $from, string $to): void
    {
        $data = $this->getCobranzas($from, $to);

        if ($subreporte === 'cobrado') {
            fputcsv($handle, ['Forma de Pago', 'Operaciones', 'Monto Cobrado']);
            foreach ($data['cobrado_por_medio'] as $c) {
                fputcsv($handle, [$c->forma_pago, $c->total_operaciones, number_format((float) $c->total_monto, 2, '.', '')]);
            }
        } else {
            fputcsv($handle, ['Venta', 'Cliente', 'Nro Cuota', 'Monto Cuota', 'Monto Pendiente', 'Vencimiento', 'Estado']);
            foreach ($data['cuotas_pendientes'] as $cuota) {
                fputcsv($handle, [
                    $cuota['venta_numero'],
                    $cuota['cliente'],
                    $cuota['numero_cuota'],
                    number_format((float) $cuota['monto'], 2, '.', ''),
                    number_format((float) $cuota['monto_pendiente'], 2, '.', ''),
                    $cuota['fecha_vencimiento'],
                    $cuota['vencida'] ? 'Vencida' : $cuota['estado'],
                ]);
            }
        }
    }
}
