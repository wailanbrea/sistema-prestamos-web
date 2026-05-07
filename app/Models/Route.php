<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = ['id'];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(Collector::class);
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'route_clients')
            ->withPivot('order_number')
            ->withTimestamps();
    }

    public function trackingSessions(): HasMany
    {
        return $this->hasMany(CollectorRouteSession::class);
    }
}
