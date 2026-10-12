<?php

namespace App\Models;

use App\Enums\StageCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A step in a hiring workflow. Each stage has a colour, an SLA and a list of
 * automations that run when a candidate enters it.
 */
class Stage extends Model
{
    protected $fillable = ['workflow_id', 'name', 'color', 'category', 'sort', 'sla_days', 'notify_team', 'automations'];

    protected function casts(): array
    {
        return [
            'category' => StageCategory::class,
            'notify_team' => 'boolean',
            'automations' => 'array',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
