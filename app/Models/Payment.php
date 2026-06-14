<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(Collector::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PaymentDetail::class);
    }

    public function collectorCommission(): HasOne
    {
        return $this->hasOne(CollectorCommission::class);
    }
}
