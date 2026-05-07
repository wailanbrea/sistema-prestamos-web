<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectorLocationPoint extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'recorded_at' => 'immutable_datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CollectorRouteSession::class, 'collector_route_session_id');
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(Collector::class);
    }
}
