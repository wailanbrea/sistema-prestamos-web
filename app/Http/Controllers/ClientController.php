<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
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

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = $this->clientService->create((int) $request->user()->company_id, $request->validated());

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
        return view('clients.edit', [
            'client' => $this->clientService->findForCompany((int) $request->user()->company_id, $client),
        ]);
    }

    public function update(UpdateClientRequest $request, int $client): RedirectResponse
    {
        $model = $this->clientService->findForCompany((int) $request->user()->company_id, $client);
        $this->clientService->update($model, $request->validated());

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
