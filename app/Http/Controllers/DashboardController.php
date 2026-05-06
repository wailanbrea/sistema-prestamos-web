<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboardService): View
    {
        $companyId = (int) $request->user()->company_id;

        return view('dashboard', [
            'metrics' => $dashboardService->summary($companyId),
        ]);
    }
}
