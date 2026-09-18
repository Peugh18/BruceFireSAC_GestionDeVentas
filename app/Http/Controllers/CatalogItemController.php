<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCatalogItemRequest;
use App\Http\Requests\UpdateCatalogItemRequest;
use App\Models\CatalogItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CatalogItemController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', CatalogItem::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'tipo' => ['nullable', Rule::in(['producto', 'servicio', 'repuesto'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = $filters['search'] ?? '';
        $tipo = $filters['tipo'] ?? '';

        $items = CatalogItem::query()
            ->when($tipo !== '', fn (Builder $query) => $query->where('tipo', $tipo))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nombre', 'like', "%{$search}%")
                        ->orWhere('codigo', 'like', "%{$search}%")
                        ->orWhere('categoria', 'like', "%{$search}%");
                });
            })
            ->orderBy('nombre')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('catalog/index', [
            'items' => $items,
            'filters' => ['search' => $search, 'tipo' => $tipo],
            'can' => [
                'create' => $request->user()->can('create', CatalogItem::class),
                'update' => $request->user()->can('catalog.update'),
            ],
            'status' => $request->session()->get('catalog_status'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', CatalogItem::class);

        return Inertia::render('catalog/create');
    }

    public function store(StoreCatalogItemRequest $request): RedirectResponse
    {
        CatalogItem::create($request->validated());

        return to_route('catalog.index')->with('catalog_status', 'Registro creado correctamente.');
    }

    public function edit(CatalogItem $catalogItem): Response
    {
        Gate::authorize('update', $catalogItem);

        return Inertia::render('catalog/edit', ['item' => $catalogItem]);
    }

    public function update(UpdateCatalogItemRequest $request, CatalogItem $catalogItem): RedirectResponse
    {
        $catalogItem->update($request->validated());

        return to_route('catalog.index')->with('catalog_status', 'Registro actualizado correctamente.');
    }
}
