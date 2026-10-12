<?php

namespace App\Services;

use App\Enums\MessageChannel;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends candidate emails and texts and keeps them in the conversation history.
 * Email goes through Laravel Mail (MAIL_MAILER); SMS is logged in demo mode and
 * is ready for a Twilio driver.
 */
class Messenger
{
    public function send(
        Candidate $candidate,
        MessageChannel $channel,
        string $body,
        ?string $subject = null,
        ?Application $application = null,
        bool $automated = false,
    ): Message {
        $message = Message::create([
            'candidate_id' => $candidate->id,
            'application_id' => $application?->id,
            'user_id' => $automated ? null : auth()->id(),
            'channel' => $channel,
            'direction' => 'outbound',
            'subject' => $channel === MessageChannel::Email ? $subject : null,
            'body' => $body,
            'automated' => $automated,
            'sent_at' => now(),
        ]);

        try {
            if ($channel === MessageChannel::Email) {
                Mail::raw($body, fn ($m) => $m->to($candidate->email, $candidate->name)->subject($subject ?: config('hiredesk.company')));
            } else {
                Log::info("SMS to {$candidate->phone}: {$body}");
            }
        } catch (\Throwable $e) {
            Log::warning('Message delivery failed: '.$e->getMessage());
        }

        Activity::log(
            ($automated ? 'Automated ' : '').($channel === MessageChannel::Email ? 'email' : 'text').' sent'.($subject ? ": {$subject}" : ''),
            $application,
            $channel->value,
        );

        return $message;
    }

    /** Merge fields available to templates. */
    public static function mergeData(Application $application): array
    {
        return [
            'first_name' => $application->candidate->first_name,
            'last_name' => $application->candidate->last_name,
            'job_title' => $application->jobOpening->title,
            'company' => config('hiredesk.company'),
            'stage' => $application->stage?->name,
        ];
    }
}
