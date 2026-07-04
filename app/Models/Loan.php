<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'first_payment_date' => 'date',
            'end_date' => 'date',
            'allows_capital_prepayment' => 'boolean',
            'contract_required' => 'boolean',
            'contract_signed' => 'boolean',
            'contract_signed_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(Collector::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(LoanQuote::class, 'quote_id');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(LoanInstallment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Adjunta como columnas calculadas el resumen de cuotas vencidas:
     * `overdue_installments_count`, `overdue_amount_due` (vencidas a ayer)
     * y `amount_due_today` (vencidas incluyendo la cuota de hoy). Se usa en
     * los listados (web y API) para mostrar deuda sin cargar las cuotas.
     */
    public function scopeWithDueSummary(Builder $query): Builder
    {
        $today = now()->toDateString();
        $pendingBalanceSql = '(case when (installment_amount - paid_principal - paid_interest) > 0 then (installment_amount - paid_principal - paid_interest) else 0 end)';
        $pendingLateFeeSql = '(case when (late_fee - paid_late_fee) > 0 then (late_fee - paid_late_fee) else 0 end)';
        $hasPendingAmountSql = "({$pendingBalanceSql} + {$pendingLateFeeSql}) > 0";

        if (empty($query->getQuery()->columns)) {
            $query->select('loans.*');
        }

        return $query
            ->selectSub(function ($query) use ($today, $hasPendingAmountSql): void {
                $query->from('loan_installments')
                    ->selectRaw('count(*)')
                    ->whereColumn('loan_installments.loan_id', 'loans.id')
                    ->whereNotIn('status', ['paid', 'cancelled'])
                    ->whereDate('due_date', '<', $today)
                    ->whereRaw($hasPendingAmountSql);
            }, 'overdue_installments_count')
            ->selectSub(function ($query) use ($today, $pendingBalanceSql, $pendingLateFeeSql, $hasPendingAmountSql): void {
                $query->from('loan_installments')
                    ->selectRaw("coalesce(sum({$pendingBalanceSql} + {$pendingLateFeeSql}), 0)")
                    ->whereColumn('loan_installments.loan_id', 'loans.id')
                    ->whereNotIn('status', ['paid', 'cancelled'])
                    ->whereDate('due_date', '<', $today)
                    ->whereRaw($hasPendingAmountSql);
            }, 'overdue_amount_due')
            ->selectSub(function ($query) use ($today, $pendingBalanceSql, $pendingLateFeeSql, $hasPendingAmountSql): void {
                $query->from('loan_installments')
                    ->selectRaw("coalesce(sum({$pendingBalanceSql} + {$pendingLateFeeSql}), 0)")
                    ->whereColumn('loan_installments.loan_id', 'loans.id')
                    ->whereNotIn('status', ['paid', 'cancelled'])
                    ->whereDate('due_date', '<=', $today)
                    ->whereRaw($hasPendingAmountSql);
            }, 'amount_due_today');
    }
}
