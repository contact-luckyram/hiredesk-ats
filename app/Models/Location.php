<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = ['name', 'city', 'state', 'zip', 'latitude', 'longitude', 'is_remote'];

    protected function casts(): array
    {
        return [
            'is_remote' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function jobOpenings(): HasMany
    {
        return $this->hasMany(JobOpening::class);
    }

    public function label(): string
    {
        return $this->is_remote ? 'Remote' : "{$this->city}, {$this->state}";
    }
}
