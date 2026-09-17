<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'company_id',
        'phone',
        'password',
        'status',
        'is_demo',
        'visible_menus',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'visible_menus' => 'array',
            'is_system_owner' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * El dueño/creador del sistema. Identidad estable vía la bandera
     * `is_system_owner` (no atada al email). Mediante Gate::before, el dueño
     * supera toda verificación de permisos (super-admin). El correo de
     * config/system.php solo se usa para marcar esta bandera en el despliegue.
     */
    public function isSystemOwner(): bool
    {
        return (bool) $this->is_system_owner;
    }

    /**
     * Indica si el usuario pertenece a una cuenta demo (solo lectura).
     * Los dueños del sistema nunca son considerados demo.
     */
    public function isDemo(): bool
    {
        if ($this->isSystemOwner()) {
            return false;
        }

        return (bool) $this->is_demo;
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
