<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountPayable extends Model
{
    use BelongsToCompany, HasFactory;

    protected $table = 'accounts_payable';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'disbursement_date' => 'date',
            'first_payment_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function creditor(): BelongsTo
    {
        return $this->belongsTo(Creditor::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(AccountPayableInstallment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AccountPayablePayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
