<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\StageCategory;
use App\Enums\Workplace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class JobOpening extends Model
{
    protected $fillable = [
        'title', 'slug', 'reference', 'department_id', 'location_id', 'workflow_id', 'questionnaire_id',
        'hiring_manager_id', 'recruiter_id', 'employment_type', 'workplace', 'salary_min', 'salary_max',
        'salary_period', 'openings', 'summary', 'description', 'status', 'is_featured', 'published_at', 'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'employment_type' => EmploymentType::class,
            'workplace' => Workplace::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'closes_at' => 'date',
            'salary_min' => 'float',
            'salary_max' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (JobOpening $job) {
            $job->slug ??= static::uniqueSlug($job->title);
            $job->reference ??= 'JOB-'.str_pad((string) (static::max('id') + 1001), 4, '0', STR_PAD_LEFT);
            $job->workflow_id ??= Workflow::default()?->id;
        });

        static::saving(function (JobOpening $job) {
            if ($job->status === JobStatus::Published && ! $job->published_at) {
                $job->published_at = now();
            }
        });
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'job';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    public function hiringManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hiring_manager_id');
    }

    public function recruiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recruiter_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', JobStatus::Published->value);
    }

    public function salaryLabel(): ?string
    {
        if (! $this->salary_min && ! $this->salary_max) {
            return null;
        }

        $fmt = fn ($v) => $this->salary_period === 'hour'
            ? Number::currency($v, 'USD', precision: 0)
            : Number::currency($v / 1000, 'USD', precision: 0).'k';

        $range = $this->salary_min && $this->salary_max
            ? $fmt($this->salary_min).'–'.$fmt($this->salary_max)
            : $fmt($this->salary_min ?: $this->salary_max);

        return $range.($this->salary_period === 'hour' ? ' / hr' : ' / yr');
    }

    public function locationLabel(): string
    {
        return $this->location?->label() ?? 'Remote';
    }

    public function activeApplicationsCount(): int
    {
        return $this->applications()->whereNull('rejected_at')->count();
    }

    public function inCategoryCount(StageCategory $category): int
    {
        return $this->applications()->whereHas('stage', fn ($q) => $q->where('category', $category->value))->count();
    }
}
