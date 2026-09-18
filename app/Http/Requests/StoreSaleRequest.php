<?php

namespace App\Http\Requests;

use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\InventoryUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('sales.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quote_id' => ['nullable', 'exists:quotes,id'],
            'client_id' => ['required', 'exists:clients,id'],
            'client_site_id' => ['nullable', 'exists:client_sites,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'tipo_comprobante' => ['required', Rule::in(['boleta', 'factura'])],
            'fecha' => ['required', 'date'],
            'condicion_pago' => ['required', Rule::in(['contado', 'credito'])],
            'observaciones' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_id' => ['required', 'exists:catalog_items,id'],
            'items.*.inventory_unit_id' => ['nullable', 'integer', 'exists:inventory_units,id'],
            'items.*.cantidad' => ['required', 'numeric', 'gt:0'],
            'items.*.precio_unitario' => ['required', 'numeric', 'gte:0'],
            'items.*.descuento' => ['nullable', 'numeric', 'gte:0'],
            'payments' => ['nullable', 'array'],
            'payments.*.forma_pago' => ['required_with:payments', Rule::in(['efectivo', 'transferencia', 'yape', 'plin', 'pos', 'deposito', 'otro'])],
            'payments.*.monto' => ['required_with:payments', 'numeric', 'gt:0'],
            'payments.*.referencia' => ['nullable', 'string'],
            'installments' => ['nullable', 'array'],
            'installments.*.numero_cuota' => ['required_with:installments', 'integer', 'gt:0'],
            'installments.*.monto' => ['required_with:installments', 'numeric', 'gt:0'],
            'installments.*.fecha_vencimiento' => ['required_with:installments', 'date'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('tipo_comprobante') === 'factura') {
                    $client = Client::find($this->input('client_id'));
                    if ($client && $client->tipo_documento !== 'ruc') {
                        $validator->errors()->add('tipo_comprobante', 'Para emitir una factura, el cliente debe tener tipo de documento RUC.');
                    }
                }

                if ($validator->errors()->hasAny(['items', 'items.*.catalog_item_id', 'items.*.inventory_unit_id', 'items.*.cantidad'])) {
                    return;
                }

                $items = collect($this->input('items', []));
                $catalogItems = CatalogItem::query()
                    ->whereIn('id', $items->pluck('catalog_item_id')->filter()->all())
                    ->get(['id', 'control_serializado'])
                    ->keyBy('id');
                $unitIds = [];

                foreach ($items as $index => $item) {
                    $catalogItem = $catalogItems->get((int) ($item['catalog_item_id'] ?? 0));
                    $unitId = $item['inventory_unit_id'] ?? null;

                    if (! $catalogItem?->control_serializado) {
                        if ($unitId !== null && $unitId !== '') {
                            $validator->errors()->add("items.{$index}.inventory_unit_id", 'Este artículo no utiliza unidades serializadas.');
                        }

                        continue;
                    }

                    if ((float) ($item['cantidad'] ?? 0) !== 1.0) {
                        $validator->errors()->add("items.{$index}.cantidad", 'La venta de una unidad serializada debe tener cantidad 1.');
                    }

                    if ($unitId === null || $unitId === '') {
                        $validator->errors()->add("items.{$index}.inventory_unit_id", 'Selecciona la unidad física que se venderá.');

                        continue;
                    }

                    if (in_array((int) $unitId, $unitIds, true)) {
                        $validator->errors()->add("items.{$index}.inventory_unit_id", 'Esta unidad ya fue seleccionada en la venta.');

                        continue;
                    }

                    $unitIds[] = (int) $unitId;

                    $available = InventoryUnit::query()
                        ->whereKey($unitId)
                        ->where('catalog_item_id', $catalogItem->id)
                        ->where('en_stock', true)
                        ->where('conforme', true)
                        ->where('estado', 'disponible')
                        ->exists();

                    if (! $available) {
                        $validator->errors()->add("items.{$index}.inventory_unit_id", 'La unidad seleccionada no está disponible para venta.');
                    }
                }
            },
        ];
    }
}
