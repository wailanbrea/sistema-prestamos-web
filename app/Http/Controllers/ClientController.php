<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Models\Route;
use App\Services\Clients\ClientDocumentService;
use App\Services\Clients\ClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function __construct(
        private readonly ClientService $clientService,
        private readonly ClientDocumentService $clientDocumentService,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status', 'risk_level']);

        return view('clients.index', [
            'clients' => $this->clientService->paginateForCompany((int) $request->user()->company_id, $filters),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        return view('clients.create', [
            'routes' => Route::query()->where('company_id', $request->user()->company_id)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $routeId = $data['route_id'] ?? null;
        unset($data['route_id']);

        $client = $this->clientService->create((int) $request->user()->company_id, $data);

        if ($routeId) {
            $client->routes()->sync([$routeId]);
        }

        return redirect()
            ->route('clients.show', $client)
            ->with('status', 'Cliente creado correctamente.');
    }

    public function show(Request $request, int $client): View
    {
        return view('clients.show', [
            'client' => $this->clientService
                ->findForCompany((int) $request->user()->company_id, $client)
                ->load('documents'),
        ]);
    }

    public function edit(Request $request, int $client): View
    {
        $model = $this->clientService->findForCompany((int) $request->user()->company_id, $client);

        return view('clients.edit', [
            'client' => $model,
            'routes' => Route::query()->where('company_id', $request->user()->company_id)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'currentRouteId' => $model->routes()->first()?->id,
        ]);
    }

    public function update(UpdateClientRequest $request, int $client): RedirectResponse
    {
        $model = $this->clientService->findForCompany((int) $request->user()->company_id, $client);
        $data = $request->validated();
        $routeId = $data['route_id'] ?? null;
        unset($data['route_id']);

        $this->clientService->update($model, $data);

        if ($routeId !== null) {
            $model->routes()->sync($routeId ? [$routeId] : []);
        }

        return redirect()
            ->route('clients.show', $model)
            ->with('status', 'Cliente actualizado correctamente.');
    }

    public function destroy(Request $request, int $client): RedirectResponse
    {
        abort_unless($request->user()?->can('clients.delete'), 403);

        $model = $this->clientService->findForCompany((int) $request->user()->company_id, $client);
        $this->clientService->delete($model);

        return redirect()
            ->route('clients.index')
            ->with('status', 'Cliente eliminado correctamente.');
    }

    public function downloadDocument(Request $request, int $client, int $document): StreamedResponse
    {
        $model = $this->clientDocumentService->findForClient((int) $request->user()->company_id, $client, $document);
        abort_unless($this->clientDocumentService->exists($model), 404);

        return Storage::disk('local')->download($model->file_path, basename($model->file_path));
    }

    public function previewDocument(Request $request, int $client, int $document): BinaryFileResponse
    {
        $model = $this->clientDocumentService->findForClient((int) $request->user()->company_id, $client, $document);
        abort_unless($this->clientDocumentService->exists($model), 404);

        return response()->file(Storage::disk('local')->path($model->file_path));
    }
}
