<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Loans\StoreLoanRequest;
use App\Http\Requests\Loans\UpdateLoanRequest;
use App\Models\Client;
use App\Models\Collector;
use App\Models\CompanySetting;
use App\Models\LoanInstallment;
use App\Models\LoanQuote;
use App\Services\Loans\InstallmentGeneratorService;
use App\Services\Loans\LoanCalculatorService;
use App\Services\Loans\LoanService;
use App\Services\Notifications\EventNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class LoanController extends Controller
{
    public function __construct(
        private readonly LoanService $loanService,
        private readonly EventNotifier $notifier,
    ) {}

    public function index(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;

        $filters = $request->only(['status', 'client_id']);

        return view('loans.index', [
            'loans' => $this->loanService->paginateForCompany($companyId, $filters),
            'summary' => $this->loanService->summaryForCompany($companyId, $filters),
            'clients' => Client::query()->forCompany($companyId)->orderBy('full_name')->get(['id', 'full_name']),
            'filters' => $filters,
            ...$this->labels(),
        ]);
    }

    public function create(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;
        $quote = null;

        if ($request->integer('quote_id')) {
            abort_unless($request->user()?->can('quotes.convert'), 403);

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
            'settings' => CompanySetting::query()->where('company_id', $companyId)->first(),
            ...$this->labels(),
        ]);
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        if ($request->filled('quote_id')) {
            abort_unless($request->user()?->can('quotes.convert'), 403);
        }

        $loan = $this->loanService->create(
            companyId: (int) $request->user()->company_id,
            userId: $request->user()?->id,
            data: $request->validated(),
        );

        $this->notifier->loanCreated($loan, $request->user()?->id);

        return redirect()
            ->route('loans.show', $loan)
            ->with('status', 'Préstamo creado correctamente.');
    }

    public function show(Request $request, int $loan): View
    {
        $model = $this->loanService->findForCompany((int) $request->user()->company_id, $loan);
        $model->loadMissing('payments');

        $validPayments = $model->payments->where('status', 'valid');
        $principalCollected = (float) $validPayments->sum('principal_paid') + (float) $validPayments->sum('capital_prepaid');
        $interestCollected = (float) $validPayments->sum('interest_paid');
        $lateFeeCollected = (float) $validPayments->sum('late_fee_paid');
        $principalPending = max(0, (float) $model->principal_amount - $principalCollected);
        $principalRecoveryRate = (float) $model->principal_amount > 0
            ? min(100, round(($principalCollected / (float) $model->principal_amount) * 100, 2))
            : 0.0;

        // Cuotas vencidas: cuotas cuyo vencimiento ya pasó y no están saldadas.
        // El monto es lo que aún se debe de cada cuota (cuota programada menos lo pagado).
        $today = now()->startOfDay();
        $overdueInstallments = $model->installments->filter(
            fn ($installment) => ! in_array($installment->status, ['paid', 'cancelled'], true)
                && $installment->due_date->lt($today),
        );
        $overdueTotal = (float) $overdueInstallments->sum(
            fn ($installment) => max(0, (float) $installment->installment_amount - (float) $installment->paid_principal - (float) $installment->paid_interest),
        );
        // Mora pendiente acumulada en las cuotas vencidas.
        $overdueLateFee = (float) $overdueInstallments->sum(
            fn ($installment) => max(0, (float) $installment->late_fee - (float) $installment->paid_late_fee),
        );

        return view('loans.show', [
            'loan' => $model,
            'financialSummary' => [
                'principal_collected' => $principalCollected,
                'principal_pending' => $principalPending,
                'principal_recovery_rate' => $principalRecoveryRate,
                'interest_collected' => $interestCollected,
                'interest_pending' => max(0, (float) $model->total_interest - $interestCollected),
                'late_fee_collected' => $lateFeeCollected,
                'overdue_total' => $overdueTotal,
                'overdue_count' => $overdueInstallments->count(),
                'overdue_late_fee' => $overdueLateFee,
                'total_due_today' => $overdueTotal + $overdueLateFee,
            ],
            ...$this->labels(),
        ]);
    }

    /**
     * Calcula el plan de cuotas en vivo (sin guardar) para la vista previa del formulario.
     */
    public function preview(Request $request, LoanCalculatorService $calculator, InstallmentGeneratorService $generator): JsonResponse
    {
        $data = $request->validate([
            'principal_amount' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:999.9999'],
            'term_quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'calculation_method' => ['required', Rule::in(['flat_interest', 'fixed_installment', 'capital_plus_interest', 'interest_only', 'french_amortization'])],
            'payment_frequency' => ['required', Rule::in(['daily', 'weekly', 'biweekly', 'monthly'])],
            'first_payment_date' => ['required', 'date'],
        ]);

        try {
            $calculation = $calculator->calculate(
                principal: (float) $data['principal_amount'],
                annualRate: (float) $data['interest_rate'],
                termQuantity: (int) $data['term_quantity'],
                method: (string) $data['calculation_method'],
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $settings = CompanySetting::query()->where('company_id', $request->user()->company_id)->first();
        $dueDates = $generator->dueDatesFor(
            $data['first_payment_date'],
            $data['payment_frequency'],
            (int) $data['term_quantity'],
            (bool) ($settings?->exclude_sundays_for_daily_loans ?? false),
        );

        $balance = (float) $data['principal_amount'];
        $rows = [];
        foreach ($calculation['installments'] as $index => $installment) {
            $balance = round($balance - $installment['principal'], 2);
            $rows[] = [
                'number' => $installment['number'],
                'due_date' => CarbonImmutable::parse($dueDates[$index])->format('d/m/Y'),
                'principal' => $installment['principal'],
                'interest' => $installment['interest'],
                'amount' => $installment['amount'],
                'balance' => max(0, $balance),
            ];
        }

        return response()->json([
            'principal' => (float) $data['principal_amount'],
            'installment_amount' => $calculation['installment_amount'],
            'total_interest' => $calculation['total_interest'],
            'total_amount' => $calculation['total_amount'],
            'installments' => $rows,
        ]);
    }

    public function edit(Request $request, int $loan): View
    {
        $companyId = (int) $request->user()->company_id;
        $model = $this->loanService->findForCompany($companyId, $loan);

        return view('loans.edit', [
            'loan' => $model,
            'hasPayments' => $model->payments()->where('status', 'valid')->exists(),
            'collectors' => Collector::query()->forCompany($companyId)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            ...$this->labels(),
        ]);
    }

    public function update(UpdateLoanRequest $request, int $loan): RedirectResponse
    {
        $companyId = (int) $request->user()->company_id;
        $model = $this->loanService->findForCompany($companyId, $loan);

        $updated = $this->loanService->update($companyId, $request->user()?->id, $model, $request->validated());

        return redirect()
            ->route('loans.show', $updated)
            ->with('status', 'Préstamo actualizado correctamente.');
    }

    public function updateLateFee(Request $request, int $loan): RedirectResponse
    {
        $companyId = (int) $request->user()->company_id;
        $model = $this->loanService->findForCompany($companyId, $loan);

        $data = $request->validate([
            'late_fee_type' => ['required', Rule::in(['none', 'fixed', 'daily_percentage', 'daily_fixed'])],
            'late_fee_value' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ], attributes: [
            'late_fee_type' => 'tipo de mora',
            'late_fee_value' => 'valor de mora',
        ]);

        $this->loanService->updateLateFee(
            companyId: $companyId,
            userId: $request->user()?->id,
            loan: $model,
            type: (string) $data['late_fee_type'],
            value: (float) $data['late_fee_value'],
        );

        $message = $data['late_fee_type'] === 'none'
            ? 'Mora quitada correctamente. Las cuotas pendientes quedaron sin mora.'
            : 'Mora actualizada y recalculada correctamente.';

        return redirect()
            ->route('loans.show', $model)
            ->with('status', $message);
    }

    public function waiveInstallmentLateFee(Request $request, int $loan, int $installment): RedirectResponse
    {
        $companyId = (int) $request->user()->company_id;
        $model = $this->loanService->findForCompany($companyId, $loan);
        $installmentModel = LoanInstallment::query()
            ->where('loan_id', $model->id)
            ->whereKey($installment)
            ->firstOrFail();

        try {
            $this->loanService->waiveInstallmentLateFee(
                companyId: $companyId,
                userId: $request->user()?->id,
                loan: $model,
                installment: $installmentModel,
                reason: 'Eliminada desde la web',
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['late_fee' => $exception->getMessage()]);
        }

        return redirect()
            ->route('loans.show', $model)
            ->with('status', 'Mora de la cuota eliminada correctamente.');
    }

    public function destroy(Request $request, int $loan): RedirectResponse
    {
        $companyId = (int) $request->user()->company_id;
        $model = $this->loanService->findForCompany($companyId, $loan);

        try {
            $this->loanService->delete($companyId, $request->user()?->id, $model);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['loan' => $exception->getMessage()]);
        }

        return redirect()
            ->route('loans.index')
            ->with('status', 'Préstamo eliminado correctamente.');
    }

    public function approve(Request $request, int $loan): RedirectResponse
    {
        $companyId = (int) $request->user()->company_id;
        $model = $this->loanService->findForCompany($companyId, $loan);

        try {
            $this->loanService->approve($companyId, $request->user()?->id, $model);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['loan' => $exception->getMessage()]);
        }

        $this->notifier->loanApproved($model, $request->user()?->id);

        return redirect()
            ->route('loans.show', $model)
            ->with('status', 'Préstamo aprobado y desembolsado correctamente.');
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
