<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateCompanySettingsRequest;
use App\Services\Settings\CompanySettingsService;
use App\Services\Users\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    public function __construct(
        private readonly CompanySettingsService $settingsService,
        private readonly UserService $userService,
    ) {
    }

    public function index(Request $request): View
    {
        $company = $request->user()->company()->with('settings')->firstOrFail();
        $filters = $request->only(['search', 'status']);

        return view('settings.index', [
            'company' => $company,
            'settings' => $company->settings,
            'users' => $this->userService->paginateForCompany((int) $company->id, $filters),
            'roles' => Role::query()->orderBy('name')->pluck('name'),
            'filters' => $filters,
        ]);
    }

    public function update(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $this->settingsService->update($request->user()->company()->with('settings')->firstOrFail(), $request->validated(), (int) $request->user()->id);

        return redirect()
            ->route('settings.index')
            ->with('status', 'Configuración actualizada correctamente.');
    }
}
