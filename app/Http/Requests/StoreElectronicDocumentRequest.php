<?php

namespace App\Http\Requests;

use App\Models\ElectronicDocument;
use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreElectronicDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('billing.issue');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Reject issuing a comprobante again once it has already been accepted.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $sale = $this->route('sale');

            if (! $sale instanceof Sale) {
                return;
            }

            $existing = ElectronicDocument::where('sale_id', $sale->id)->first();

            if ($existing !== null && $existing->estado === 'aceptado') {
                $validator->errors()->add('estado', 'Esta venta ya tiene un comprobante electronico aceptado por SUNAT.');
            }
        });
    }
}
