<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleStoreRequest;
use App\Http\Requests\VehicleUpdateRequest;
use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;

class VehicleController extends Controller
{
    /**
     * Store a newly created vehicle for the given client.
     */
    public function store(VehicleStoreRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->vehicles()->create($request->validated());

        return to_route('clients.show', $client)->with('status', 'Vehiculo registrado correctamente.');
    }

    /**
     * Update the specified vehicle.
     */
    public function update(VehicleUpdateRequest $request, Client $client, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $client);

        $vehicle->update($request->validated());

        return to_route('clients.show', $client)->with('status', 'Vehiculo actualizado correctamente.');
    }
}
