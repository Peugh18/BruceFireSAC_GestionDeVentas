<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\UpdateQuoteRequest;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Quote;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    /**
     * Display a listing of quotes.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Quote::class);

        $search = $request->string('search')->toString();
        $estado = $request->string('estado')->toString();

        $quotes = Quote::query()
            ->with(['client', 'vendedor'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where('numero', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('razon_social', 'like', "%{$search}%")
                            ->orWhere('nombre_comercial', 'like', "%{$search}%")
                            ->orWhere('numero_documento', 'like', "%{$search}%");
                    });
            })
            ->when($estado !== '' && $estado !== 'todos', fn ($query) => $query->where('estado', $estado))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('quotes/index', [
            'quotes' => $quotes,
            'filters' => [
                'search' => $search,
                'estado' => $estado,
            ],
        ]);
    }

    /**
     * Show the form for creating a new quote.
     */
    public function create(): Response
    {
        $this->authorize('create', Quote::class);

        $clients = Client::query()
            ->where('activo', true)
            ->with(['sites' => fn ($q) => $q->where('activo', true), 'vehicles' => fn ($q) => $q->where('activo', true)])
            ->orderBy('razon_social')
            ->get();

        $catalogItems = CatalogItem::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return Inertia::render('quotes/create', [
            'clients' => $clients,
            'catalogItems' => $catalogItems,
        ]);
    }

    /**
     * Store a newly created quote in storage.
     */
    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $quote = DB::transaction(function () use ($validated, $request) {
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

            $quote = Quote::create([
                'client_id' => $validated['client_id'],
                'client_site_id' => $validated['client_site_id'] ?? null,
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'vendedor_user_id' => $request->user()->id,
                'fecha' => $validated['fecha'],
                'vigencia' => $validated['vigencia'],
                'condicion_propuesta' => $validated['condicion_propuesta'],
                'observaciones' => $validated['observaciones'] ?? null,
                'subtotal' => $subtotalSum,
                'igv' => $igv,
                'total' => $total,
                'estado' => 'borrador',
            ]);

            foreach ($itemsData as $itemData) {
                $quote->items()->create($itemData);
            }

            return $quote;
        });

        return to_route('quotes.show', $quote)->with('status', 'Cotizacion creada correctamente.');
    }

    /**
     * Display the specified quote.
     */
    public function show(Quote $quote): Response
    {
        $this->authorize('view', $quote);

        $quote->load([
            'client',
            'site',
            'vehicle',
            'vendedor',
            'items.catalogItem',
            'sale',
        ]);

        return Inertia::render('quotes/show', [
            'quote' => $quote,
        ]);
    }

    /**
     * Show the form for editing the specified quote.
     */
    public function edit(Quote $quote): Response
    {
        $this->authorize('update', $quote);

        $quote->load(['items.catalogItem']);

        $clients = Client::query()
            ->where('activo', true)
            ->orWhere('id', $quote->client_id)
            ->with(['sites', 'vehicles'])
            ->orderBy('razon_social')
            ->get();

        $catalogItems = CatalogItem::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return Inertia::render('quotes/edit', [
            'quote' => $quote,
            'clients' => $clients,
            'catalogItems' => $catalogItems,
        ]);
    }

    /**
     * Update the specified quote in storage.
     */
    public function update(UpdateQuoteRequest $request, Quote $quote): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $quote) {
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

            $quote->update([
                'client_id' => $validated['client_id'],
                'client_site_id' => $validated['client_site_id'] ?? null,
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'fecha' => $validated['fecha'],
                'vigencia' => $validated['vigencia'],
                'condicion_propuesta' => $validated['condicion_propuesta'],
                'observaciones' => $validated['observaciones'] ?? null,
                'subtotal' => $subtotalSum,
                'igv' => $igv,
                'total' => $total,
            ]);

            $quote->items()->delete();
            foreach ($itemsData as $itemData) {
                $quote->items()->create($itemData);
            }
        });

        return to_route('quotes.show', $quote)->with('status', 'Cotizacion actualizada correctamente.');
    }

    /**
     * Duplicate a quote into a new draft quote.
     */
    public function duplicate(Quote $quote, Request $request): RedirectResponse
    {
        $this->authorize('create', Quote::class);

        $quote->load(['items']);

        $newQuote = DB::transaction(function () use ($quote, $request) {
            $newQuote = Quote::create([
                'client_id' => $quote->client_id,
                'client_site_id' => $quote->client_site_id,
                'vehicle_id' => $quote->vehicle_id,
                'vendedor_user_id' => $request->user()->id,
                'fecha' => now()->toDateString(),
                'vigencia' => now()->addDays(15)->toDateString(),
                'subtotal' => $quote->subtotal,
                'igv' => $quote->igv,
                'total' => $quote->total,
                'condicion_propuesta' => $quote->condicion_propuesta,
                'observaciones' => $quote->observaciones,
                'estado' => 'borrador',
            ]);

            foreach ($quote->items as $item) {
                $newQuote->items()->create([
                    'catalog_item_id' => $item->catalog_item_id,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                    'descuento' => $item->descuento,
                    'subtotal' => $item->subtotal,
                ]);
            }

            return $newQuote;
        });

        return to_route('quotes.show', $newQuote)->with('status', 'Cotizacion duplicada correctamente.');
    }

    /**
     * Change quote status (e.g. emitida, enviada, aceptada, rechazada, anulada).
     */
    public function changeStatus(Quote $quote, Request $request): RedirectResponse
    {
        $this->authorize('update', $quote);

        $request->validate([
            'estado' => ['required', 'in:borrador,emitida,enviada,aceptada,rechazada,vencida,anulada'],
        ]);

        $quote->update(['estado' => $request->string('estado')->toString()]);

        return to_route('quotes.show', $quote)->with('status', 'Estado de la cotizacion actualizado.');
    }

    /**
     * Convert an accepted or issued quote into a sale.
     */
    public function convert(Quote $quote, Request $request): RedirectResponse
    {
        $this->authorize('convert', $quote);

        $quote->load(['items.catalogItem']);

        $sale = DB::transaction(function () use ($quote, $request) {
            $sale = Sale::create([
                'quote_id' => $quote->id,
                'client_id' => $quote->client_id,
                'client_site_id' => $quote->client_site_id,
                'vehicle_id' => $quote->vehicle_id,
                'vendedor_user_id' => $request->user()->id,
                'fecha' => now()->toDateString(),
                'condicion_pago' => $quote->condicion_propuesta,
                'subtotal' => $quote->subtotal,
                'igv' => $quote->igv,
                'total' => $quote->total,
                'estado' => 'completada',
                'observaciones' => $quote->observaciones,
            ]);

            foreach ($quote->items as $item) {
                $sale->items()->create([
                    'catalog_item_id' => $item->catalog_item_id,
                    'cantidad' => $item->cantidad,
                    'precio_unitario' => $item->precio_unitario,
                    'descuento' => $item->descuento,
                    'subtotal' => $item->subtotal,
                ]);

                // Hook: si el item tiene control_serializado = true, el vendedor posteriormente
                // escanea los barcode exactos de las unidades físicas para vincularlas.
                if ($item->catalogItem?->control_serializado) {
                    // Hook para escaneo de unidades serializadas
                }
            }

            $quote->update(['estado' => 'convertida']);

            return $sale;
        });

        return to_route('sales.show', $sale)->with('status', 'Cotizacion convertida a venta exitosamente.');
    }
}
