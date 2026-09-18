<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\CatalogItem;
use App\Models\CreditDebitNote;
use App\Models\ElectronicDocument;
use App\Models\Sale;
use App\Services\Billing\BillingService;
use App\Services\Sales\SaleItemProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function __construct(
        private readonly BillingService $billingService,
        private readonly SaleItemProcessor $saleItemProcessor,
    ) {}

    /**
     * Display a listing of sales.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Sale::class);

        $search = $request->string('search')->toString();
        $condicion = $request->string('condicion')->toString();

        $sales = $this->filteredSalesQuery($request)
            ->with(['client', 'vendedor', 'quote'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('sales/index', [
            'sales' => $sales,
            'filters' => [
                'search' => $search,
                'condicion' => $condicion,
            ],
            'hasInternalPdf' => false,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Sale::class);

        $filename = 'ventas-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($request): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Numero', 'Cliente', 'Documento', 'Cotizacion', 'Fecha', 'Condicion', 'Subtotal', 'IGV', 'Total', 'Estado']);

            $this->filteredSalesQuery($request)
                ->with(['client:id,razon_social,numero_documento', 'quote:id,numero'])
                ->chunkById(200, function (Collection $sales) use ($handle): void {
                    foreach ($sales as $sale) {
                        fputcsv($handle, [
                            $sale->numero,
                            $sale->client?->razon_social,
                            $sale->client?->numero_documento,
                            $sale->quote?->numero,
                            $sale->fecha?->toDateString(),
                            $sale->condicion_pago,
                            $sale->subtotal,
                            $sale->igv,
                            $sale->total,
                            $sale->estado,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredSalesQuery(Request $request): Builder
    {
        $search = $request->string('search')->toString();
        $condicion = $request->string('condicion')->toString();

        return Sale::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('numero', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('razon_social', 'like', "%{$search}%")
                            ->orWhere('nombre_comercial', 'like', "%{$search}%")
                            ->orWhere('numero_documento', 'like', "%{$search}%");
                    });
            })
            ->when($condicion !== '' && $condicion !== 'todos', fn ($query) => $query->where('condicion_pago', $condicion));
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create(): Response
    {
        $this->authorize('create', Sale::class);

        $catalogItems = CatalogItem::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return Inertia::render('sales/create', [
            'catalogItems' => $catalogItems,
        ]);
    }

    /**
     * Store a newly created sale in storage.
     */
    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $sale = DB::transaction(function () use ($validated, $request) {
            $subtotalSum = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $cantidad = (float) $item['cantidad'];
                $precio = (float) $item['precio_unitario'];
                $descuento = isset($item['descuento']) ? (float) $item['descuento'] : 0;
                $itemSubtotal = round(($cantidad * $precio) - $descuento, 2);
                $subtotalSum += $itemSubtotal;

                $itemsData[] = [
                    'catalog_item_id' => $item['catalog_item_id'],
                    'inventory_unit_id' => $item['inventory_unit_id'] ?? null,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'descuento' => $descuento,
                    'subtotal' => $itemSubtotal,
                ];
            }

            $igv = round($subtotalSum * 0.18, 2);
            $total = round($subtotalSum + $igv, 2);

            $sale = Sale::create([
                'quote_id' => $validated['quote_id'] ?? null,
                'client_id' => $validated['client_id'],
                'client_site_id' => $validated['client_site_id'] ?? null,
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'vendedor_user_id' => $request->user()->id,
                'fecha' => $validated['fecha'],
                'condicion_pago' => $validated['condicion_pago'],
                'subtotal' => $subtotalSum,
                'igv' => $igv,
                'total' => $total,
                'estado' => 'completada',
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            $this->saleItemProcessor->process($sale, $itemsData, $request->user());

            if ($validated['condicion_pago'] === 'contado' && ! empty($validated['payments'])) {
                foreach ($validated['payments'] as $payment) {
                    $sale->payments()->create([
                        'forma_pago' => $payment['forma_pago'],
                        'monto' => $payment['monto'],
                        'referencia' => $payment['referencia'] ?? null,
                    ]);
                }
            } elseif ($validated['condicion_pago'] === 'credito' && ! empty($validated['installments'])) {
                foreach ($validated['installments'] as $installment) {
                    $sale->installments()->create([
                        'numero_cuota' => $installment['numero_cuota'],
                        'monto' => $installment['monto'],
                        'monto_pendiente' => $installment['monto'],
                        'fecha_vencimiento' => $installment['fecha_vencimiento'],
                        'estado' => 'pendiente',
                    ]);
                }
            }

            $this->billingService->issue($sale, $validated['tipo_comprobante']);

            return $sale;
        });

        return to_route('sales.show', $sale)->with('status', 'Venta registrada correctamente.');
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale): Response
    {
        $this->authorize('view', $sale);

        $sale->load([
            'client',
            'site',
            'vehicle',
            'vendedor',
            'quote',
            'items.catalogItem',
            'items.inventoryUnit',
            'payments',
            'installments',
        ]);

        $electronicDocument = ElectronicDocument::where('sale_id', $sale->id)
            ->with('creditDebitNotes')
            ->first();

        return Inertia::render('sales/show', [
            'sale' => $sale,
            'electronicDocument' => $electronicDocument,
            'tipoLabels' => ElectronicDocument::TIPO_LABELS,
            'estadoLabels' => ElectronicDocument::ESTADO_LABELS,
            'noteTipoLabels' => CreditDebitNote::TIPO_LABELS,
            'noteEstadoLabels' => CreditDebitNote::ESTADO_LABELS,
            'noteMotivoLabels' => [
                'nota_credito' => CreditDebitNote::MOTIVO_CREDITO_LABELS,
                'nota_debito' => CreditDebitNote::MOTIVO_DEBITO_LABELS,
            ],
            'company' => [
                'ruc' => config('billing.sunat.ruc'),
                'razon_social' => config('billing.sunat.razon_social'),
                'nombre_comercial' => config('billing.sunat.nombre_comercial'),
            ],
        ]);
    }
}
