<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    protected $fillable = ['name', 'description', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function stages(): HasMany
    {
        return $this->hasMany(Stage::class)->orderBy('sort');
    }

    public function jobOpenings(): HasMany
    {
        return $this->hasMany(JobOpening::class);
    }

    public function firstStage(): ?Stage
    {
        return $this->stages()->first();
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->first() ?? static::first();
    }
}
