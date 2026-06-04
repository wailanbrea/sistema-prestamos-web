<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AccountsPayable\StoreAccountPayablePaymentRequest;
use App\Http\Requests\AccountsPayable\StoreAccountPayableRequest;
use App\Http\Requests\AccountsPayable\StoreCreditorRequest;
use App\Services\AccountsPayable\AccountPayableService;
use App\Services\AccountsPayable\CreditorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountPayableController extends Controller
{
    public function __construct(
        private readonly AccountPayableService $accountService,
        private readonly CreditorService $creditorService,
    ) {
    }

    public function index(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;
        $filters = $request->only(['search', 'creditor_id', 'status']);

        return view('accounts-payable.index', [
            'accounts' => $this->accountService->paginateForCompany($companyId, $filters),
            'creditors' => $this->creditorService->listForCompany($companyId),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        return view('accounts-payable.create', [
            'creditors' => $this->creditorService->listForCompany((int) $request->user()->company_id),
        ]);
    }

    public function store(StoreAccountPayableRequest $request): RedirectResponse
    {
        $account = $this->accountService->create(
            (int) $request->user()->company_id,
            $request->validated(),
            (int) $request->user()->id,
        );

        return redirect()
            ->route('accounts-payable.show', $account)
            ->with('status', 'Cuenta por pagar registrada correctamente.');
    }

    public function show(Request $request, int $accountPayable): View
    {
        return view('accounts-payable.show', [
            'account' => $this->accountService->findForCompany((int) $request->user()->company_id, $accountPayable),
        ]);
    }

    public function storeCreditor(StoreCreditorRequest $request): RedirectResponse
    {
        $this->creditorService->create((int) $request->user()->company_id, $request->validated());

        return back()->with('status', 'Acreedor registrado correctamente.');
    }

    public function storePayment(StoreAccountPayablePaymentRequest $request, int $accountPayable): RedirectResponse
    {
        $this->accountService->registerPayment(
            (int) $request->user()->company_id,
            $accountPayable,
            $request->validated(),
            (int) $request->user()->id,
        );

        return redirect()
            ->route('accounts-payable.show', $accountPayable)
            ->with('status', 'Pago registrado correctamente.');
    }
}
