<?php

namespace App\Models;

use App\Enums\MessageChannel;
use Illuminate\Database\Eloquent\Model;

/**
 * Branded email / SMS template. Merge fields: {first_name}, {last_name}, {job_title},
 * {company}, {stage}, {portal_link}.
 */
class EmailTemplate extends Model
{
    protected $fillable = ['name', 'channel', 'subject', 'body'];

    protected function casts(): array
    {
        return ['channel' => MessageChannel::class];
    }

    public static function render(?string $text, array $data): string
    {
        return strtr((string) $text, collect($data)->mapWithKeys(fn ($v, $k) => ['{'.$k.'}' => (string) $v])->all());
    }
}
