<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $fillable = ['application_id', 'candidate_id', 'employee_id', 'user_id', 'type', 'description', 'created_at'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $description, ?Application $application = null, string $type = 'event', ?Employee $employee = null): self
    {
        return static::create([
            'application_id' => $application?->id,
            'candidate_id' => $application?->candidate_id ?? $employee?->candidate_id,
            'employee_id' => $employee?->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'description' => $description,
        ]);
    }
}
