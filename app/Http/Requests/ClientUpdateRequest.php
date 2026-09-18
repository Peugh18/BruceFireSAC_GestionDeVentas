<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('clients.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $client = $this->route('client');

        return [
            'tipo_documento' => ['required', Rule::in(['dni', 'ruc', 'ce', 'pasaporte'])],
            'numero_documento' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clients')
                    ->where(fn ($query) => $query->where('tipo_documento', $this->input('tipo_documento')))
                    ->ignore($client),
            ],
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'direccion_fiscal' => ['nullable', 'string', 'max:255'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'provincia' => ['nullable', 'string', 'max:100'],
            'distrito' => ['nullable', 'string', 'max:100'],
            'ubigeo' => ['nullable', 'string', 'max:6'],
            'activo' => ['boolean'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
