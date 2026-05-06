<?php

declare(strict_types=1);

namespace App\Services\Loans;

use App\Models\Client;
use App\Models\Company;
use App\Models\Loan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class LateStatusRefreshService
{
    public function __construct(private readonly LateFeeService $lateFeeService)
    {
    }

    /**
     * @return array{companies:int,loans_checked:int,installments_checked:int,loans_marked_late:int,loans_restored_active:int,clients_marked_late:int,clients_restored_active:int}
     */
    public function refresh(?int $companyId = null, ?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $stats = [
            'companies' => 0,
            'loans_checked' => 0,
            'installments_checked' => 0,
            'loans_marked_late' => 0,
            'loans_restored_active' => 0,
            'clients_marked_late' => 0,
            'clients_restored_active' => 0,
        ];

        Company::query()
            ->when($companyId, fn ($query) => $query->whereKey($companyId))
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(50, function ($companies) use (&$stats, $today): void {
                foreach ($companies as $company) {
                    $stats['companies']++;
                    $this->refreshCompany((int) $company->id, $today, $stats);
                }
            });

        return $stats;
    }

    /**
     * @param array<string, int> $stats
     */
    private function refreshCompany(int $companyId, CarbonImmutable $today, array &$stats): void
    {
        DB::transaction(function () use ($companyId, $today, &$stats): void {
            Loan::query()
                ->with('installments')
                ->forCompany($companyId)
                ->whereIn('status', ['active', 'late'])
                ->orderBy('id')
                ->chunkById(100, function ($loans) use ($today, &$stats): void {
                    foreach ($loans as $loan) {
                        $stats['loans_checked']++;

                        foreach ($loan->installments as $installment) {
                            $stats['installments_checked']++;
                            $this->lateFeeService->refreshInstallment($loan, $installment, $today);
                        }

                        $hasLateInstallments = $loan->installments()
                            ->where('status', 'late')
                            ->exists();

                        if ($hasLateInstallments && $loan->status !== 'late') {
                            $loan->forceFill(['status' => 'late'])->save();
                            $stats['loans_marked_late']++;
                        }

                        if (! $hasLateInstallments && $loan->status === 'late') {
                            $loan->forceFill(['status' => 'active'])->save();
                            $stats['loans_restored_active']++;
                        }
                    }
                });

            $this->refreshClientStatuses($companyId, $stats);
        });
    }

    /**
     * @param array<string, int> $stats
     */
    private function refreshClientStatuses(int $companyId, array &$stats): void
    {
        Client::query()
            ->forCompany($companyId)
            ->whereIn('status', ['active', 'moroso'])
            ->orderBy('id')
            ->chunkById(100, function ($clients) use (&$stats): void {
                foreach ($clients as $client) {
                    $hasLateLoans = $client->loans()
                        ->where('status', 'late')
                        ->exists();

                    if ($hasLateLoans && $client->status !== 'moroso') {
                        $client->forceFill(['status' => 'moroso'])->save();
                        $stats['clients_marked_late']++;
                    }

                    if (! $hasLateLoans && $client->status === 'moroso') {
                        $client->forceFill(['status' => 'active'])->save();
                        $stats['clients_restored_active']++;
                    }
                }
            });
    }
}
