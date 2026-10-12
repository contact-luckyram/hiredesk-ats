<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'city', 'state', 'zip', 'linkedin_url', 'current_title',
        'years_experience', 'source', 'resume_path', 'resume_text', 'skills', 'tags', 'in_talent_pool', 'sms_opt_in',
    ];

    protected $appends = ['name'];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'tags' => 'array',
            'in_talent_pool' => 'boolean',
            'sms_opt_in' => 'boolean',
        ];
    }

    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class)->latest('applied_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->latest('sent_at');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest();
    }

    public function locationLabel(): ?string
    {
        return $this->city ? trim("{$this->city}, {$this->state}", ', ') : null;
    }
}
