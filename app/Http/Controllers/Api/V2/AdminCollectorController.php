<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collectors\StoreCollectorRequest;
use App\Http\Requests\Collectors\UpdateCollectorRequest;
use App\Models\Collector;
use App\Models\CollectorCommission;
use App\Models\Loan;
use App\Services\Collectors\CollectorCommissionService;
use App\Services\Collectors\CollectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AdminCollectorController extends Controller
{
    public function __construct(
        private readonly CollectorService $collectorService,
        private readonly CollectorCommissionService $collectorCommissionService,
    ) {}

    public function storeCollector(StoreCollectorRequest $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $collector = $this->collectorService->create($companyId, $request->validated());

        return response()->json(['data' => $this->collectorDetailPayload($collector->load('commissions'))], 201);
    }

    public function showCollector(Request $request, int $collector): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $model = Collector::query()
            ->where('company_id', $companyId)
            ->with([
                'commissions' => fn ($q) => $q->orderByDesc('id')->limit(50),
            ])
            ->whereKey($collector)
            ->firstOrFail();

        $stats = [
            'active_loans' => Loan::query()->forCompany($companyId)->where('collector_id', $model->id)->whereIn('status', ['active', 'late'])->count(),
            'late_loans' => Loan::query()->forCompany($companyId)->where('collector_id', $model->id)->where('status', 'late')->count(),
        ];

        return response()->json(['data' => [...$this->collectorDetailPayload($model), 'stats' => $stats]]);
    }

    public function updateCollector(UpdateCollectorRequest $request, int $collector): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $model = Collector::query()->where('company_id', $companyId)->whereKey($collector)->firstOrFail();
        $updated = $this->collectorService->update($model, $request->validated());

        return response()->json(['data' => $this->collectorDetailPayload($updated->load('commissions'))]);
    }

    public function payCollectorCommission(Request $request, int $collector, int $commission): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        try {
            $paid = $this->collectorCommissionService->pay(
                companyId: $companyId,
                collectorId: $collector,
                commissionId: $commission,
                paidBy: (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $this->commissionPayload($paid)]);
    }

    /** Cobradores activos de la empresa (para el selector del formulario de préstamo). */
    public function collectors(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $collectors = Collector::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => $collectors->map(fn (Collector $c): array => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function collectorDetailPayload(Collector $collector): array
    {
        $commissions = $collector->relationLoaded('commissions') ? $collector->commissions : collect();
        $pending = $commissions->where('status', 'pending');

        return [
            'id' => $collector->id,
            'name' => $collector->name,
            'phone' => $collector->phone,
            'commission_type' => $collector->commission_type,
            'commission_base' => $collector->commission_base ?? 'payment_total',
            'commission_value' => (float) $collector->commission_value,
            'status' => $collector->status,
            'commission_summary' => [
                'total_generated' => (float) $commissions->sum('commission_amount'),
                'total_pending' => (float) $pending->sum('commission_amount'),
                'total_paid' => (float) $commissions->where('status', 'paid')->sum('commission_amount'),
            ],
            'pending_commissions' => $pending->map(fn (CollectorCommission $c): array => $this->commissionPayload($c))->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function commissionPayload(CollectorCommission $commission): array
    {
        return [
            'id' => $commission->id,
            'commission_type' => $commission->commission_type,
            'commission_value' => (float) $commission->commission_value,
            'base_amount' => (float) $commission->base_amount,
            'commission_amount' => (float) $commission->commission_amount,
            'status' => $commission->status,
            'paid_at' => $commission->paid_at?->toDateTimeString(),
            'receipt_number' => $commission->payment?->receipt_number,
        ];
    }
}
