<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Platform extends BaseModel
{
    protected $table = 'platforms';

    protected $fillable = [
        'name',
    ];

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }
}
