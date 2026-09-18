<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    /**
     * Display the Gerente operational dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('dashboard', $this->dashboardService->build());
    }
}
