<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceOrderActaController extends Controller
{
    /**
     * Display the Acta de Conformidad for a ServiceOrder.
     */
    public function show(Request $request, ServiceOrder $serviceOrder): Response
    {
        $this->authorize('view', $serviceOrder);

        $serviceOrder->load([
            'client',
            'clientSite',
            'tecnico:id,name',
            'equipment' => function ($query) {
                $query->orderBy('id');
            },
            'pickups' => function ($query) {
                $query->orderByDesc('id');
            },
        ]);

        return Inertia::render('service-orders/acta', [
            'serviceOrder' => $serviceOrder,
        ]);
    }
}
