<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientSiteStoreRequest;
use App\Http\Requests\ClientSiteUpdateRequest;
use App\Models\Client;
use App\Models\ClientSite;
use Illuminate\Http\RedirectResponse;

class ClientSiteController extends Controller
{
    /**
     * Store a newly created site for the given client.
     */
    public function store(ClientSiteStoreRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->sites()->create($request->validated());

        return to_route('clients.show', $client)->with('status', 'Sede registrada correctamente.');
    }

    /**
     * Update the specified site.
     */
    public function update(ClientSiteUpdateRequest $request, Client $client, ClientSite $site): RedirectResponse
    {
        $this->authorize('update', $client);

        $site->update($request->validated());

        return to_route('clients.show', $client)->with('status', 'Sede actualizada correctamente.');
    }
}
