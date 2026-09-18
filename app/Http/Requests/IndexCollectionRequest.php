<?php

namespace App\Http\Requests;

use App\Models\SaleInstallment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', SaleInstallment::class);
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(['pendiente', 'parcial', 'vencida'])],
        ];
    }
}
