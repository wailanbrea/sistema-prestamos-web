<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Loan;
use App\Services\Contracts\ContractService;
use App\Services\Contracts\ContractShareService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractController extends Controller
{
    public function __construct(
        private readonly ContractService $contractService,
        private readonly ContractShareService $shareService,
    ) {
    }

    public function index(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;
        $filters = $request->only(['status', 'client_id', 'loan_id', 'date_from', 'date_to']);

        $contracts = Contract::query()
            ->with(['client:id,full_name', 'loan:id,loan_number'])
            ->forCompany($companyId)
            ->when($filters['status'] ?? null, fn (Builder $q, string $s) => $q->where('status', $s))
            ->when($filters['client_id'] ?? null, fn (Builder $q, $c) => $q->where('client_id', $c))
            ->when($filters['loan_id'] ?? null, fn (Builder $q, $l) => $q->where('loan_id', $l))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'] ?? null, fn (Builder $q, string $d) => $q->whereDate('created_at', '<=', $d))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('contracts.index', [
            'contracts' => $contracts,
            'filters' => $filters,
            'statuses' => $this->statusLabels(),
        ]);
    }

    public function show(Request $request, string $contract): View
    {
        $model = $this->findForCompany($request, $contract);
        $model->load([
            'client', 'loan', 'document',
            'signatures' => fn ($q) => $q->latest('id'),
            'events' => fn ($q) => $q->latest('id'),
            'versions' => fn ($q) => $q->orderByDesc('version'),
        ]);

        return view('contracts.show', [
            'contract' => $model,
            'statuses' => $this->statusLabels(),
            'whatsappUrl' => $this->shareService->whatsappUrl($model),
            'signingUrl' => $model->isFinalized() ? null : $this->shareService->signingUrl($model),
        ]);
    }

    public function generate(Request $request, int $loan): RedirectResponse
    {
        $companyId = (int) $request->user()->company_id;
        $loanModel = Loan::query()->forCompany($companyId)->whereKey($loan)->firstOrFail();

        try {
            $contract = $this->contractService->generate(
                $companyId,
                $loanModel,
                $request->input('contract_type', 'loan_contract'),
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->route('loans.show', $loan)->withErrors(['contract' => $e->getMessage()]);
        }

        return redirect()->route('contracts.show', $contract->uuid)->with('status', 'Contrato generado correctamente.');
    }

    public function send(Request $request, string $contract): RedirectResponse
    {
        $model = $this->findForCompany($request, $contract);

        try {
            $this->contractService->markSent($model, (int) $request->user()->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['contract' => $e->getMessage()]);
        }

        return redirect()->route('contracts.show', $model->uuid)->with('status', 'Contrato marcado como enviado. Comparte el enlace de firma.');
    }

    public function regenerate(Request $request, string $contract): RedirectResponse
    {
        $model = $this->findForCompany($request, $contract);

        try {
            $this->contractService->regenerate($model, (int) $request->user()->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['contract' => $e->getMessage()]);
        }

        return redirect()->route('contracts.show', $model->uuid)->with('status', 'Contrato regenerado.');
    }

    public function cancel(Request $request, string $contract): RedirectResponse
    {
        $model = $this->findForCompany($request, $contract);
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:255']])['reason'] ?? null;

        try {
            $this->contractService->cancel($model, (int) $request->user()->id, $reason);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['contract' => $e->getMessage()]);
        }

        return redirect()->route('contracts.show', $model->uuid)->with('status', 'Contrato anulado.');
    }

    public function download(Request $request, string $contract): StreamedResponse
    {
        $model = $this->findForCompany($request, $contract);
        abort_if($model->document === null, 404);
        abort_unless(Storage::disk('local')->exists($model->document->file_path), 404);

        $this->contractService->logEvent($model, 'downloaded', 'Contrato descargado desde el panel.');

        return Storage::disk('local')->download($model->document->file_path, $model->contract_number.'.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function findForCompany(Request $request, string $uuid): Contract
    {
        return Contract::query()
            ->forCompany((int) $request->user()->company_id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * @return array<string, string>
     */
    private function statusLabels(): array
    {
        return [
            'draft' => 'Borrador',
            'generated' => 'Generado',
            'sent' => 'Enviado',
            'viewed' => 'Visto',
            'signed' => 'Firmado',
            'rejected' => 'Rechazado',
            'cancelled' => 'Anulado',
            'expired' => 'Vencido',
        ];
    }
}
