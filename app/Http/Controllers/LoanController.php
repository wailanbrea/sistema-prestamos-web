<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Loans\StoreLoanRequest;
use App\Models\Client;
use App\Models\Collector;
use App\Models\LoanQuote;
use App\Services\Loans\LoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function __construct(private readonly LoanService $loanService)
    {
    }

    public function index(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;

        return view('loans.index', [
            'loans' => $this->loanService->paginateForCompany($companyId, $request->only(['status', 'client_id'])),
            'clients' => Client::query()->forCompany($companyId)->orderBy('full_name')->get(['id', 'full_name']),
            'filters' => $request->only(['status', 'client_id']),
            ...$this->labels(),
        ]);
    }

    public function create(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;
        $quote = null;

        if ($request->integer('quote_id')) {
            $quote = LoanQuote::query()
                ->with('client')
                ->forCompany($companyId)
                ->whereKey($request->integer('quote_id'))
                ->where('status', '!=', 'converted')
                ->firstOrFail();
        }

        return view('loans.create', [
            'quote' => $quote,
            'clients' => Client::query()->forCompany($companyId)->where('status', '!=', 'blocked')->orderBy('full_name')->get(['id', 'full_name', 'identification']),
            'collectors' => Collector::query()->forCompany($companyId)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            ...$this->labels(),
        ]);
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        $loan = $this->loanService->create(
            companyId: (int) $request->user()->company_id,
            userId: $request->user()?->id,
            data: $request->validated(),
        );

        return redirect()
            ->route('loans.show', $loan)
            ->with('status', 'Préstamo creado correctamente.');
    }

    public function show(Request $request, int $loan): View
    {
        return view('loans.show', [
            'loan' => $this->loanService->findForCompany((int) $request->user()->company_id, $loan),
            ...$this->labels(),
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function labels(): array
    {
        return [
            'frequencyLabels' => config('loan_labels.frequencies'),
            'methodLabels' => config('loan_labels.methods'),
            'loanStatusLabels' => config('loan_labels.loan_statuses'),
        ];
    }
}
