<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanySetting extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'allow_partial_payments' => 'boolean',
            'allow_payment_cancellation' => 'boolean',
            'require_approval_for_loans' => 'boolean',
            'exclude_sundays_for_daily_loans' => 'boolean',
            'route_visit_radius_meters' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
