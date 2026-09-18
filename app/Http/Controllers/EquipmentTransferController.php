<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipmentTransferRequest;
use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class EquipmentTransferController extends Controller
{
    /**
     * Transfer the given equipment to another client/site, keeping the
     * previous history intact.
     */
    public function store(EquipmentTransferRequest $request, Equipment $equipment): RedirectResponse
    {
        $this->authorize('transfer', $equipment);

        $data = $request->validated();

        DB::transaction(function () use ($equipment, $data, $request) {
            $equipment->transfers()->create([
                'origen_client_id' => $equipment->client_id,
                'origen_client_site_id' => $equipment->client_site_id,
                'destino_client_id' => $data['destino_client_id'],
                'destino_client_site_id' => $data['destino_client_site_id'] ?? null,
                'fecha' => $data['fecha'],
                'motivo' => $data['motivo'],
                'responsable_user_id' => $request->user()->id,
                'observacion' => $data['observacion'] ?? null,
            ]);

            $equipment->events()->create([
                'tipo' => 'transferencia',
                'descripcion' => "Transferido al cliente #{$data['destino_client_id']}. Motivo: {$data['motivo']}",
                'fecha' => $data['fecha'],
                'user_id' => $request->user()->id,
            ]);

            $equipment->update([
                'client_id' => $data['destino_client_id'],
                'client_site_id' => $data['destino_client_site_id'] ?? null,
            ]);
        });

        return to_route('equipment.show', $equipment)->with('status', 'Equipo transferido correctamente.');
    }
}
