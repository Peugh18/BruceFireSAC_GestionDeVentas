<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\CatalogItem;
use App\Models\CreditDebitNote;
use App\Models\ElectronicDocument;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    /**
     * Display a listing of sales.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Sale::class);

        $search = $request->string('search')->toString();
        $condicion = $request->string('condicion')->toString();

        $sales = Sale::query()
            ->with(['client', 'vendedor', 'quote'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('numero', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('razon_social', 'like', "%{$search}%")
                            ->orWhere('nombre_comercial', 'like', "%{$search}%")
                            ->orWhere('numero_documento', 'like', "%{$search}%");
                    });
            })
            ->when($condicion !== '' && $condicion !== 'todos', fn ($query) => $query->where('condicion_pago', $condicion))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('sales/index', [
            'sales' => $sales,
            'filters' => [
                'search' => $search,
                'condicion' => $condicion,
            ],
        ]);
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

            foreach ($itemsData as $itemData) {
                $sale->items()->create($itemData);
            }

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
