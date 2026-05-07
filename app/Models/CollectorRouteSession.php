<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectorRouteSession extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'started_at' => 'immutable_datetime',
        'ended_at' => 'immutable_datetime',
        'last_location_at' => 'immutable_datetime',
        'last_latitude' => 'decimal:7',
        'last_longitude' => 'decimal:7',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(Collector::class);
    }

    public function locationPoints(): HasMany
    {
        return $this->hasMany(CollectorLocationPoint::class);
    }

    public function visitEvents(): HasMany
    {
        return $this->hasMany(RouteVisitEvent::class);
    }
}
