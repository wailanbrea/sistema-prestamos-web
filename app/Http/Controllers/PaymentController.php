<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Payments\CancelPaymentRequest;
use App\Http\Requests\Payments\StorePaymentRequest;
use App\Models\Collector;
use App\Models\Loan;
use App\Services\Payments\PaymentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'payment_method', 'date_from', 'date_to']);

        return view('payments.index', [
            'payments' => $this->paymentService->paginateForCompany((int) $request->user()->company_id, $filters),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;
        $selectedLoan = null;

        if ($request->filled('loan_id')) {
            $selectedLoan = Loan::query()
                ->with(['client:id,full_name', 'collector:id,name'])
                ->forCompany($companyId)
                ->whereIn('status', ['active', 'late'])
                ->whereKey((int) $request->integer('loan_id'))
                ->firstOrFail();
        }

        return view('payments.create', [
            'loans' => $this->activeLoans($companyId),
            'collectors' => $this->activeCollectors($companyId),
            'selectedLoan' => $selectedLoan,
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        try {
            $payment = $this->paymentService->register([
                ...$request->validated(),
                'created_by' => $request->user()->id,
            ]);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', 'Cobro registrado correctamente.');
    }

    public function show(Request $request, int $payment): View
    {
        return view('payments.show', [
            'payment' => $this->paymentService->findForCompany((int) $request->user()->company_id, $payment),
        ]);
    }

    public function cancel(CancelPaymentRequest $request, int $payment): RedirectResponse
    {
        try {
            $this->paymentService->cancel(
                companyId: (int) $request->user()->company_id,
                paymentId: $payment,
                cancelledBy: (int) $request->user()->id,
                reason: $request->validated('cancellation_reason'),
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['cancellation_reason' => $exception->getMessage()]);
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('status', 'Cobro anulado correctamente.');
    }

    /**
     * @return Collection<int, Loan>
     */
    private function activeLoans(int $companyId): Collection
    {
        return Loan::query()
            ->with(['client:id,full_name', 'collector:id,name'])
            ->forCompany($companyId)
            ->whereIn('status', ['active', 'late'])
            ->orderBy('loan_number')
            ->get();
    }

    /**
     * @return Collection<int, Collector>
     */
    private function activeCollectors(int $companyId): Collection
    {
        return Collector::query()
            ->forCompany($companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
