<?php

namespace App\Models;

use App\Enums\StageCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** A candidate's application to one job, moving through the job's workflow stages. */
class Application extends Model
{
    protected $fillable = [
        'candidate_id', 'job_opening_id', 'stage_id', 'source', 'score', 'knocked_out', 'rating', 'answers', 'eeo',
        'cover_letter', 'position', 'applied_at', 'stage_entered_at', 'hired_at', 'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'eeo' => 'array',
            'knocked_out' => 'boolean',
            'applied_at' => 'datetime',
            'stage_entered_at' => 'datetime',
            'hired_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function jobOpening(): BelongsTo
    {
        return $this->belongsTo(JobOpening::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class)->orderBy('scheduled_at');
    }

    public function scorecards(): HasMany
    {
        return $this->hasMany(Scorecard::class)->latest();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class)->latest();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->latest('sent_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest();
    }

    public function offer(): HasOne
    {
        return $this->hasOne(Offer::class)->latestOfMany();
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('rejected_at')->whereNull('hired_at');
    }

    public function category(): ?StageCategory
    {
        return $this->stage?->category;
    }

    public function daysInStage(): int
    {
        return (int) ($this->stage_entered_at ?? $this->applied_at ?? $this->created_at)->diffInDays(now());
    }

    public function isOverSla(): bool
    {
        return $this->stage?->sla_days
            && ! $this->category()?->isClosed()
            && $this->daysInStage() > $this->stage->sla_days;
    }

    public function scoreColor(): string
    {
        return match (true) {
            $this->score === null => 'gray',
            $this->knocked_out || $this->score < 50 => 'danger',
            $this->score < 75 => 'warning',
            default => 'success',
        };
    }

    public function averageScorecard(): ?float
    {
        $avg = $this->scorecards->avg('rating');

        return $avg ? round($avg, 1) : null;
    }

    public function title(): string
    {
        return $this->candidate?->name.' — '.$this->jobOpening?->title;
    }
}
