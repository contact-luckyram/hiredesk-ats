<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Employee extends Model
{
    protected $fillable = [
        'candidate_id', 'application_id', 'department_id', 'location_id', 'onboarding_template_id', 'employee_number',
        'first_name', 'last_name', 'email', 'phone', 'job_title', 'manager_name', 'start_date', 'status', 'portal_token', 'profile',
    ];

    protected $appends = ['name'];

    protected function casts(): array
    {
        return [
            'status' => EmployeeStatus::class,
            'start_date' => 'date',
            'profile' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee) {
            $employee->portal_token ??= Str::random(40);
            $employee->employee_number ??= 'EMP-'.str_pad((string) (static::max('id') + 1001), 5, '0', STR_PAD_LEFT);
        });
    }

    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function onboardingTemplate(): BelongsTo
    {
        return $this->belongsTo(OnboardingTemplate::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(OnboardingTask::class)->orderBy('sort');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest();
    }

    public function progress(): int
    {
        $total = $this->tasks->count();

        return $total ? (int) round($this->tasks->whereNotNull('completed_at')->count() / $total * 100) : 0;
    }

    public function portalUrl(): string
    {
        return route('portal.show', $this->portal_token);
    }
}
