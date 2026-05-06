<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoanQuotes\StoreLoanQuoteRequest;
use App\Models\Client;
use App\Services\Loans\LoanQuoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanQuoteController extends Controller
{
    public function __construct(private readonly LoanQuoteService $loanQuoteService)
    {
    }

    public function index(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;

        return view('loan-quotes.index', [
            'quotes' => $this->loanQuoteService->paginateForCompany($companyId, $request->only(['status', 'client_id'])),
            'clients' => Client::query()->forCompany($companyId)->orderBy('full_name')->get(['id', 'full_name']),
            'filters' => $request->only(['status', 'client_id']),
            ...$this->labels(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('loan-quotes.create', [
            'clients' => Client::query()
                ->forCompany((int) $request->user()->company_id)
                ->where('status', '!=', 'blocked')
                ->orderBy('full_name')
                ->get(['id', 'full_name', 'identification']),
            ...$this->labels(),
        ]);
    }

    public function store(StoreLoanQuoteRequest $request): RedirectResponse
    {
        $quote = $this->loanQuoteService->create(
            companyId: (int) $request->user()->company_id,
            userId: $request->user()?->id,
            data: $request->validated(),
        );

        return redirect()
            ->route('loan-quotes.show', $quote)
            ->with('status', 'Cotización creada correctamente.');
    }

    public function show(Request $request, int $quote): View
    {
        $model = $this->loanQuoteService->findForCompany((int) $request->user()->company_id, $quote);
        $calculation = $this->loanQuoteService->calculate($model->toArray());

        return view('loan-quotes.show', [
            'quote' => $model,
            'calculation' => $calculation,
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
            'statusLabels' => config('loan_labels.quote_statuses'),
        ];
    }
}
