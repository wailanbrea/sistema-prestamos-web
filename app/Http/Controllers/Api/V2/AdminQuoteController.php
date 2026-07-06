<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoanQuotes\StoreLoanQuoteRequest;
use App\Models\LoanQuote;
use App\Services\Loans\LoanQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AdminQuoteController extends Controller
{
    use BuildsApiPayloads;

    public function __construct(
        private readonly LoanQuoteService $loanQuoteService,
    ) {}

    public function quotes(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $quotes = LoanQuote::query()
            ->forCompany($companyId)
            ->with('client:id,full_name,identification')
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json([
            'data' => $quotes->through(fn (LoanQuote $quote): array => $this->quotePayload($quote))->items(),
            'meta' => $this->paginationMeta($quotes),
        ]);
    }

    public function storeQuote(StoreLoanQuoteRequest $request): JsonResponse
    {
        $quote = $this->loanQuoteService->create(
            companyId: (int) $request->user()->company_id,
            userId: $request->user()?->id,
            data: $request->validated(),
        );

        return response()->json([
            'data' => $this->quotePayload($quote->loadMissing('client'), withSchedule: true),
        ], 201);
    }

    public function quote(Request $request, int $quote): JsonResponse
    {
        $model = $this->loanQuoteService->findForCompany((int) $request->user()->company_id, $quote);

        return response()->json([
            'data' => $this->quotePayload($model, withSchedule: true),
        ]);
    }

    public function destroyQuote(Request $request, int $quote): JsonResponse
    {
        $model = $this->loanQuoteService->findForCompany((int) $request->user()->company_id, $quote);

        try {
            $this->loanQuoteService->delete($model);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Cotización eliminada.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function quotePayload(LoanQuote $quote, bool $withSchedule = false): array
    {
        $calculation = $this->loanQuoteService->calculate($quote->toArray());

        $payload = [
            'id' => $quote->id,
            'client' => $quote->client ? [
                'id' => $quote->client->id,
                'full_name' => $quote->client->full_name,
                'identification' => $quote->client->identification,
            ] : null,
            'amount' => (float) $quote->amount,
            'interest_rate' => (float) $quote->interest_rate,
            'interest_type' => $quote->interest_type,
            'payment_frequency' => $quote->payment_frequency,
            'calculation_method' => $quote->calculation_method,
            'term_quantity' => (int) $quote->term_quantity,
            'status' => $quote->status,
            'start_date' => $quote->start_date?->toDateString(),
            'first_payment_date' => $quote->first_payment_date?->toDateString(),
            'created_at' => $quote->created_at?->toDateTimeString(),
            'installment_amount' => (float) $calculation['installment_amount'],
            'total_interest' => (float) $calculation['total_interest'],
            'total_amount' => (float) $calculation['total_amount'],
        ];

        if ($withSchedule) {
            $payload['installments'] = $calculation['installments'];
        }

        return $payload;
    }
}
