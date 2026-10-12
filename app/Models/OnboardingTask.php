<?php

namespace App\Models;

use App\Enums\TaskAssignee;
use App\Enums\TaskType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTask extends Model
{
    protected $fillable = [
        'employee_id', 'title', 'description', 'type', 'assignee', 'sort', 'due_date', 'data', 'signed_name',
        'signature', 'file_path', 'completed_at',
    ];

    protected $hidden = ['signature'];

    protected function casts(): array
    {
        return [
            'type' => TaskType::class,
            'assignee' => TaskAssignee::class,
            'due_date' => 'date',
            'data' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isDone(): bool
    {
        return $this->completed_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isDone() && $this->due_date && $this->due_date->isPast() && ! $this->due_date->isToday();
    }
}
