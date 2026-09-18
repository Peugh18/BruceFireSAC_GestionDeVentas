<?php

namespace App\Http\Requests;

use App\Models\Certificate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCertificateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('certificates.issue');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_order_id' => ['required', 'exists:service_orders,id'],
            'tipo' => ['required', Rule::in(array_keys(Certificate::TIPO_LABELS))],
            'fecha_emision' => ['required', 'date'],
            'fecha_vigencia' => ['nullable', 'date', 'after_or_equal:fecha_emision'],
            'observaciones' => ['nullable', 'string'],
            'equipment_ids' => ['required', 'array', 'min:1'],
            'equipment_ids.*' => ['integer', 'exists:equipment,id'],
        ];
    }
}
