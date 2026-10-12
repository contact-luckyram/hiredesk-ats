<?php

namespace App\Models;

use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Interview extends Model
{
    protected $fillable = ['application_id', 'title', 'type', 'scheduled_at', 'duration_minutes', 'location', 'interviewer_ids', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'type' => InterviewType::class,
            'status' => InterviewStatus::class,
            'scheduled_at' => 'datetime',
            'interviewer_ids' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function scorecards(): HasMany
    {
        return $this->hasMany(Scorecard::class);
    }

    public function interviewers(): Collection
    {
        return User::whereIn('id', $this->interviewer_ids ?? [])->get();
    }

    /** iCalendar invite so the interview lands in Outlook / Google Calendar. */
    public function toIcs(): string
    {
        $start = $this->scheduled_at->clone()->utc();
        $end = $start->clone()->addMinutes($this->duration_minutes);
        $candidate = $this->application?->candidate?->name;
        $job = $this->application?->jobOpening?->title;
        $esc = fn (?string $v) => str_replace([',', ';', "\n"], ['\,', '\;', '\n'], (string) $v);

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//HireDesk//Interviews//EN',
            'METHOD:REQUEST',
            'BEGIN:VEVENT',
            'UID:interview-'.$this->id.'@hiredesk',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->format('Ymd\THis\Z'),
            'DTEND:'.$end->format('Ymd\THis\Z'),
            'SUMMARY:'.$esc("{$this->title}: {$candidate}"),
            'DESCRIPTION:'.$esc("Interview for {$job}"),
            'LOCATION:'.$esc($this->location),
            'END:VEVENT',
            'END:VCALENDAR',
        ])."\r\n";
    }
}
