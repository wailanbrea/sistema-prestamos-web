<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collector extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'commission_value' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(CollectorCommission::class);
    }

    public function routeSessions(): HasMany
    {
        return $this->hasMany(CollectorRouteSession::class);
    }
}
