<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Contract extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'generated_at' => 'datetime',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'signed_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Contract $contract): void {
            if (empty($contract->uuid)) {
                $contract->uuid = (string) Str::uuid();
            }
        });
    }

    public function loan(): BelongsTo
    {
        // withTrashed: un contrato sigue siendo verificable aunque el préstamo
        // se haya eliminado (soft delete). Evita loan = null al verificar/firmar.
        return $this->belongsTo(Loan::class)->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ContractEvent::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContractVersion::class);
    }

    public function latestSignature(): ?ContractSignature
    {
        return $this->signatures()->latest('id')->first();
    }

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, ['signed', 'cancelled', 'rejected', 'expired'], true);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
