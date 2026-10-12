<?php

namespace App\Models;

use App\Enums\MessageChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['candidate_id', 'application_id', 'user_id', 'channel', 'direction', 'subject', 'body', 'automated', 'sent_at'];

    protected function casts(): array
    {
        return [
            'channel' => MessageChannel::class,
            'automated' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
