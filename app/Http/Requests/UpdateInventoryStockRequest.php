<?php

namespace App\Http\Requests;

use App\Models\InventoryStock;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('adjust', InventoryStock::class);
    }

    public function rules(): array
    {
        return ['stock_minimo' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999.999']];
    }

    public function messages(): array
    {
        return [
            'stock_minimo.required' => 'Ingresa el stock mínimo.',
            'stock_minimo.numeric' => 'El stock mínimo debe ser un número.',
            'stock_minimo.decimal' => 'El stock mínimo admite hasta tres decimales.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
            'stock_minimo.max' => 'El stock mínimo excede el límite permitido.',
        ];
    }
}
