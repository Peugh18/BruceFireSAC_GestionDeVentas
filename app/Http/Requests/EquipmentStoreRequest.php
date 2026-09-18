<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipmentStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('equipment.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'client_site_id' => [
                'nullable',
                'integer',
                Rule::exists('client_sites', 'id')->where(fn ($query) => $query->where('client_id', $this->input('client_id'))),
            ],
            'vehicle_id' => [
                'nullable',
                'integer',
                Rule::exists('vehicles', 'id')->where(fn ($query) => $query->where('client_id', $this->input('client_id'))),
            ],
            'origen' => ['required', Rule::in(['vendido_bruce_fire', 'externo', 'desconocido'])],
            'tipo_equipo' => ['required', 'string', 'max:255'],
            'agente' => ['nullable', 'string', 'max:255'],
            'capacidad' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'serie_fabricante' => ['nullable', 'string', 'max:255'],
            'anio_fabricacion' => ['nullable', 'string', 'max:10'],
            'ubicacion' => ['nullable', 'string', 'max:255'],
            'estado' => ['sometimes', Rule::in([
                'activo',
                'fuera_de_servicio',
                'reemplazado',
                'retirado',
                'baja_definitiva',
                'no_localizado',
            ])],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}
