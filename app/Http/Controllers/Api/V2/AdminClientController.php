<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Models\Client;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Services\Clients\ClientRegistrationLinkService;
use App\Services\Clients\ClientService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminClientController extends Controller
{
    use BuildsApiPayloads;

    public function __construct(
        private readonly ClientService $clientService,
        private readonly ClientRegistrationLinkService $registrationLinkService,
    ) {}

    /**
     * Alta de cliente desde la app móvil: mismo FormRequest que la web
     * (clients.create se valida en authorize()).
     */
    public function storeClient(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->create((int) $request->user()->company_id, $request->validated());

        return response()->json([
            'data' => $this->clientPayload($client),
        ], 201);
    }

    public function updateClient(UpdateClientRequest $request, int $client): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $clientModel = $this->clientService->findForCompany($companyId, $client);
        $updated = $this->clientService->update($clientModel, $request->validated());

        return response()->json([
            'data' => $this->clientDetailData($companyId, $updated->load(['references', 'routes'])),
        ]);
    }

    public function deleteClient(Request $request, int $client): JsonResponse
    {
        abort_unless($request->user()?->can('clients.delete'), 403);
        $companyId = (int) $request->user()->company_id;
        $clientModel = $this->clientService->findForCompany($companyId, $client);

        try {
            $this->clientService->delete($clientModel);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Cliente eliminado.']);
    }

    /** Genera un link de auto-registro de cliente y devuelve la URL del formulario y el link de WhatsApp. */
    public function createRegistrationLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:30'],
        ]);

        abort_unless($request->user()?->can('clients.create'), 403);

        $link = $this->registrationLinkService->create(
            companyId: (int) $request->user()->company_id,
            data: $validated,
            createdBy: (int) $request->user()->id,
        );

        $formUrl = route('client-registration.show', ['token' => $link->token]);

        $whatsappUrl = null;
        $phone = $link->recipient_phone ? preg_replace('/\D+/', '', (string) $link->recipient_phone) : null;
        if ($phone) {
            $message = ($link->recipient_name ? "Hola {$link->recipient_name}, " : 'Hola, ')
                .'completa tu formulario de registro aquí: '
                .$formUrl;
            $whatsappUrl = 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
        }

        return response()->json([
            'data' => [
                'form_url' => $formUrl,
                'whatsapp_url' => $whatsappUrl,
            ],
        ], 201);
    }

    public function clients(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $search = $request->string('search')->toString();

        $clients = Client::query()
            ->forCompany($companyId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('identification', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json([
            'data' => $clients->through(fn (Client $client): array => $this->clientPayload($client))->items(),
            'meta' => $this->paginationMeta($clients),
        ]);
    }

    public function client(Request $request, int $client): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $clientModel = Client::query()
            ->forCompany($companyId)
            ->with([
                'references:id,client_id,name,phone,relationship,address',
                'routes:id,name',
            ])
            ->whereKey($client)
            ->firstOrFail();

        return response()->json(['data' => $this->clientDetailData($companyId, $clientModel)]);
    }

    /**
     * Estructura completa del detalle de cliente (datos + resumen financiero + préstamos
     * + cuotas + pagos recientes). Compartida por el GET de detalle y por updateClient
     * para que ambos devuelvan exactamente la misma forma (la app espera `summary`).
     *
     * @return array<string, mixed>
     */
    private function clientDetailData(int $companyId, Client $clientModel): array
    {
        $loans = Loan::query()
            ->forCompany($companyId)
            ->with('client:id,code,full_name,identification,phone,address,status,risk_level')
            ->where('client_id', $clientModel->id)
            ->orderByRaw("case when status in ('active', 'late') then 0 else 1 end")
            ->orderByDesc('id')
            ->get();

        $installments = LoanInstallment::query()
            ->whereIn('status', ['pending', 'partial', 'late'])
            ->with('loan.client:id,code,full_name,identification,phone,address,status,risk_level')
            ->whereHas('loan', fn (Builder $query): Builder => $query->forCompany($companyId)->where('client_id', $clientModel->id))
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $payments = Payment::query()
            ->forCompany($companyId)
            ->with(['loan.client', 'collector'])
            ->where('client_id', $clientModel->id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return [
            ...$this->clientDetailPayload($clientModel),
            'summary' => $this->clientFinancialSummary($companyId, (int) $clientModel->id),
            'loans' => $loans->map(fn (Loan $loan): array => $this->loanPayload($loan))->values(),
            'pending_installments' => $installments->map(fn (LoanInstallment $installment): array => $this->installmentPayload($installment))->values(),
            'recent_payments' => $payments->map(fn (Payment $payment): array => $this->paymentPayload($payment))->values(),
        ];
    }

    /**
     * Resumen financiero del cliente a nivel empresa (sin restricción de cobrador).
     *
     * @return array<string, mixed>
     */
    private function clientFinancialSummary(int $companyId, int $clientId): array
    {
        $loanQuery = Loan::query()->forCompany($companyId)->where('client_id', $clientId);
        $paymentQuery = Payment::query()->forCompany($companyId)->where('client_id', $clientId)->where('status', 'valid');
        $pendingInstallmentQuery = LoanInstallment::query()
            ->whereIn('status', ['pending', 'partial', 'late'])
            ->whereHas('loan', fn (Builder $query): Builder => $query->forCompany($companyId)->where('client_id', $clientId));

        $openLoanQuery = (clone $loanQuery)->whereIn('status', ['active', 'late']);

        return [
            'active_loans' => (clone $openLoanQuery)->count(),
            'late_loans' => (clone $loanQuery)->where('status', 'late')->count(),
            'total_principal' => (float) (clone $loanQuery)->sum('principal_amount'),
            'remaining_balance' => (float) (clone $loanQuery)->sum('remaining_balance'),
            'pending_principal' => max(0.0, (float) (clone $openLoanQuery)->sum(DB::raw('principal_amount - paid_principal'))),
            'pending_interest' => max(0.0, (float) (clone $openLoanQuery)->sum(DB::raw('total_interest - paid_interest'))),
            'pending_installments' => (clone $pendingInstallmentQuery)->count(),
            'late_installments' => (clone $pendingInstallmentQuery)->where('status', 'late')->count(),
            'max_days_late' => (int) (clone $pendingInstallmentQuery)->max('days_late'),
            'total_paid' => (float) (clone $paymentQuery)->sum('amount'),
            'last_payment_date' => (clone $paymentQuery)->max('payment_date'),
        ];
    }
}
