<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** tasks: [{title, description, type, assignee, due_offset_days, fields[]}] — due offset is relative to start date. */
class OnboardingTemplate extends Model
{
    protected $fillable = ['name', 'description', 'tasks', 'is_default'];

    protected function casts(): array
    {
        return ['tasks' => 'array', 'is_default' => 'boolean'];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->first() ?? static::first();
    }
}
