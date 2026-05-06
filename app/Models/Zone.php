<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zone extends Model
{
    use BelongsToCompany, HasFactory;

    protected $guarded = ['id'];

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }
}
