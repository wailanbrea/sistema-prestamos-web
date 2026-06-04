<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientRegistrationLink extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usedClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'used_client_id');
    }

    public function isAvailable(): bool
    {
        return $this->used_at === null && $this->revoked_at === null;
    }
}
