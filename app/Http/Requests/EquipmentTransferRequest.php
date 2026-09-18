<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipmentTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('equipment.transfer');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'destino_client_id' => ['required', 'integer', 'exists:clients,id'],
            'destino_client_site_id' => [
                'nullable',
                'integer',
                Rule::exists('client_sites', 'id')->where(fn ($query) => $query->where('client_id', $this->input('destino_client_id'))),
            ],
            'fecha' => ['required', 'date'],
            'motivo' => ['required', 'string', 'max:255'],
            'observacion' => ['nullable', 'string'],
        ];
    }
}
