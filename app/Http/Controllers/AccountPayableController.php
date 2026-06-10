<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AccountsPayable\StoreAccountPayablePaymentRequest;
use App\Http\Requests\AccountsPayable\StoreAccountPayableRequest;
use App\Http\Requests\AccountsPayable\StoreCreditorRequest;
use App\Http\Requests\AccountsPayable\UpdateAccountPayableRequest;
use App\Services\AccountsPayable\AccountPayableService;
use App\Services\AccountsPayable\CreditorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

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

    public function edit(Request $request, int $accountPayable): View
    {
        $account = $this->accountService->findForCompany((int) $request->user()->company_id, $accountPayable);

        if ($account->payments->isNotEmpty()) {
            abort(403, 'Esta cuenta por pagar ya tiene pagos registrados y no puede editarse.');
        }

        return view('accounts-payable.edit', [
            'account' => $account,
            'creditors' => $this->creditorService->listForCompany((int) $request->user()->company_id),
        ]);
    }

    public function update(UpdateAccountPayableRequest $request, int $accountPayable): RedirectResponse
    {
        $account = $this->accountService->update(
            (int) $request->user()->company_id,
            $accountPayable,
            $request->validated(),
            (int) $request->user()->id,
        );

        return redirect()
            ->route('accounts-payable.show', $account)
            ->with('status', 'Cuenta por pagar actualizada correctamente.');
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

    public function destroy(Request $request, int $accountPayable): RedirectResponse
    {
        try {
            $this->accountService->delete(
                (int) $request->user()->company_id,
                $accountPayable,
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['account_payable' => $exception->getMessage()]);
        }

        return redirect()
            ->route('accounts-payable.index')
            ->with('status', 'Cuenta por pagar eliminada correctamente.');
    }
}
