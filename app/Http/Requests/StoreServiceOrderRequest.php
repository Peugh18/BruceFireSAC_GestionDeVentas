<?php

namespace App\Http\Requests;

use App\Models\ServiceOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ServiceOrder::class);
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('activo', true)],
            'client_site_id' => [
                'nullable', 'integer',
                Rule::exists('client_sites', 'id')->where('client_id', $this->integer('client_id'))->where('activo', true),
            ],
            'vehicle_id' => [
                'nullable', 'integer',
                Rule::exists('vehicles', 'id')->where('client_id', $this->integer('client_id'))->where('activo', true),
            ],
            'tipo_servicio' => ['required', Rule::in(array_keys(ServiceOrder::SERVICE_TYPES))],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'prioridad' => ['required', Rule::in(array_keys(ServiceOrder::PRIORITIES))],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'tecnico_user_id' => [
                Rule::prohibitedIf(! $this->user()->can('service_orders.assign')),
                ...AssignServiceOrderRequest::technicianRules(),
            ],
            'equipment_ids' => ['required', 'array', 'min:1', 'max:200'],
            'equipment_ids.*' => [
                'required', 'integer', 'distinct',
                Rule::exists('equipment', 'id')
                    ->where('client_id', $this->integer('client_id'))
                    ->when($this->filled('client_site_id'), fn ($rule) => $rule->where('client_site_id', $this->integer('client_site_id')))
                    ->when($this->filled('vehicle_id'), fn ($rule) => $rule->where('vehicle_id', $this->integer('vehicle_id'))),
            ],
            'estado' => ['prohibited'],
            'codigo' => ['prohibited'],
            'sale_id' => ['prohibited'],
            'precio' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'client_id.exists' => 'Selecciona un cliente activo.',
            'client_site_id.exists' => 'La sede debe estar activa y pertenecer al cliente.',
            'vehicle_id.exists' => 'El vehículo debe estar activo y pertenecer al cliente.',
            'equipment_ids.required' => 'Selecciona al menos un equipo.',
            'equipment_ids.*.exists' => 'Cada equipo debe pertenecer al cliente y a la sede o vehículo seleccionados.',
            'equipment_ids.*.distinct' => 'No puedes repetir un equipo en la orden.',
            'tecnico_user_id.prohibited' => 'No tienes permiso para asignar técnicos.',
            'tecnico_user_id.in' => 'Selecciona un usuario con permiso para ejecutar servicios.',
            'estado.prohibited' => 'Las nuevas órdenes comienzan pendientes de recepción.',
            'sale_id.prohibited' => 'La venta de origen se vincula desde el módulo Comercial.',
            'precio.prohibited' => 'Las órdenes de servicio no gestionan precios.',
        ];
    }
}
