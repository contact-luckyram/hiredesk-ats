<?php

namespace App\Models;

use App\Enums\OfferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class Offer extends Model
{
    protected $fillable = [
        'application_id', 'approved_by', 'salary', 'salary_period', 'start_date', 'expires_at', 'body', 'status',
        'token', 'signed_name', 'signature', 'sent_at', 'signed_at',
    ];

    protected $hidden = ['signature'];

    protected function casts(): array
    {
        return [
            'status' => OfferStatus::class,
            'start_date' => 'date',
            'expires_at' => 'date',
            'sent_at' => 'datetime',
            'signed_at' => 'datetime',
            'salary' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Offer $offer) {
            $offer->token ??= Str::random(40);
        });
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function salaryLabel(): string
    {
        return Number::currency($this->salary, 'USD', precision: $this->salary_period === 'hour' ? 2 : 0)
            .($this->salary_period === 'hour' ? ' per hour' : ' per year');
    }

    public function signingUrl(): string
    {
        return route('offers.show', $this->token);
    }

    public static function defaultBody(Application $application, float $salary, string $period, string $startDate): string
    {
        $c = $application->candidate;
        $job = $application->jobOpening;
        $pay = Number::currency($salary, 'USD', precision: $period === 'hour' ? 2 : 0).($period === 'hour' ? ' per hour' : ' per year');
        $company = config('hiredesk.company');

        return "Dear {$c->first_name},\n\n"
            ."We are delighted to offer you the position of {$job->title} with {$company}, starting on "
            .\Illuminate\Support\Carbon::parse($startDate)->format('F j, Y').".\n\n"
            ."Your compensation will be {$pay}, paid bi-weekly, and you will be eligible for our benefits package "
            ."including medical, dental and vision coverage, 401(k) matching and paid time off.\n\n"
            ."This offer is contingent on successful completion of a background check and verification of your "
            ."eligibility to work. Please review and sign this letter to accept.\n\n"
            ."We look forward to welcoming you to the team.\n\n"
            ."Sincerely,\nHuman Resources\n{$company}";
    }
}
