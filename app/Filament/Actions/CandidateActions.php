<?php

namespace App\Filament\Actions;

use App\Enums\MessageChannel;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\EmailTemplate;
use App\Models\JobOpening;
use App\Services\Messenger;
use App\Services\PipelineService;
use App\Services\ResumeParser;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

/** Actions shared by candidate and application screens. */
class CandidateActions
{
    /** Send an email or text; $resolve returns [Candidate, ?Application] for the current record. */
    public static function message(MessageChannel $channel, \Closure $resolve): Action
    {
        $isSms = $channel === MessageChannel::Sms;

        return Action::make($isSms ? 'text' : 'email')
            ->label($isSms ? 'Text' : 'Email')
            ->icon($isSms ? Heroicon::OutlinedChatBubbleLeftRight : Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->modalHeading(fn () => ($isSms ? 'Text ' : 'Email ').$resolve()[0]->name)
            ->modalDescription(fn () => $isSms ? ($resolve()[0]->phone ?: 'No phone number on file') : $resolve()[0]->email)
            ->modalSubmitActionLabel('Send')
            ->schema([
                Select::make('template_id')
                    ->label('Template')
                    ->options(fn () => EmailTemplate::where('channel', $channel->value)->orderBy('name')->pluck('name', 'id'))
                    ->placeholder('Write your own')
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set) use ($resolve) {
                        [$candidate, $application] = $resolve();
                        if (! $state || ! ($template = EmailTemplate::find($state))) {
                            return;
                        }
                        $data = $application ? Messenger::mergeData($application) : ['first_name' => $candidate->first_name, 'last_name' => $candidate->last_name, 'company' => config('hiredesk.company'), 'job_title' => '', 'stage' => ''];
                        $set('subject', EmailTemplate::render($template->subject, $data));
                        $set('body', EmailTemplate::render($template->body, $data));
                    })
                    ->native(false),
                TextInput::make('subject')->required()->visible(! $isSms),
                Textarea::make('body')->label('Message')->required()->rows($isSms ? 3 : 8)->maxLength($isSms ? 320 : 10000),
            ])
            ->action(function (array $data) use ($channel, $resolve) {
                [$candidate, $application] = $resolve();
                app(Messenger::class)->send($candidate, $channel, $data['body'], $data['subject'] ?? null, $application);
                Notification::make()->title($channel === MessageChannel::Sms ? 'Text sent' : 'Email sent')->success()->send();
            });
    }

    public static function addToJob(\Closure $candidate): Action
    {
        return Action::make('addToJob')
            ->label('Add to job')
            ->icon(Heroicon::OutlinedBriefcase)
            ->schema([
                Select::make('job_opening_id')
                    ->label('Job')
                    ->options(fn () => JobOpening::whereNot('status', 'closed')->orderBy('title')
                        ->whereDoesntHave('applications', fn ($q) => $q->where('candidate_id', $candidate()->id))
                        ->pluck('title', 'id'))
                    ->required()
                    ->searchable(),
            ])
            ->action(function (array $data) use ($candidate) {
                $application = self::createApplication($candidate(), JobOpening::findOrFail($data['job_opening_id']), 'Added by recruiter');
                Notification::make()->title("Added to {$application->jobOpening->title}")->success()
                    ->actions([Action::make('open')->url(ApplicationResource::getUrl('view', ['record' => $application]))])
                    ->send();
            });
    }

    public static function createApplication(Candidate $candidate, JobOpening $job, string $source): Application
    {
        $application = Application::firstOrCreate(
            ['candidate_id' => $candidate->id, 'job_opening_id' => $job->id],
            ['source' => $source, 'applied_at' => now()],
        );

        if ($application->wasRecentlyCreated) {
            Activity::log("Added to {$job->title} by ".(auth()->user()?->name ?? 'recruiter'), $application, 'applied');
            if ($stage = $job->workflow?->firstStage()) {
                app(PipelineService::class)->moveTo($application, $stage, runAutomations: false);
            }
        }

        return $application;
    }

    /** Re-reads an uploaded resume and merges detected skills into the profile. */
    public static function parseResume(Candidate $candidate): void
    {
        if (! $candidate->resume_path || ! Storage::disk('local')->exists($candidate->resume_path)) {
            return;
        }

        $parser = app(ResumeParser::class);
        $text = $parser->extractText(Storage::disk('local')->path($candidate->resume_path));
        $parsed = $parser->parse($text);

        $candidate->update([
            'resume_text' => $text ?: $candidate->resume_text,
            'skills' => array_values(array_unique(array_merge($candidate->skills ?? [], $parsed['skills']))),
            'years_experience' => $candidate->years_experience ?? $parsed['years_experience'],
            'phone' => $candidate->phone ?? $parsed['phone'],
        ]);
    }
}
