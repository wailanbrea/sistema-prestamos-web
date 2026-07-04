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
            'default_interest_rate' => 'decimal:4',
            'default_late_fee_value' => 'decimal:2',
            'default_map_latitude' => 'decimal:7',
            'default_map_longitude' => 'decimal:7',
            'enabled_loan_calculation_methods' => 'array',
            'enabled_payment_allocation_modes' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
