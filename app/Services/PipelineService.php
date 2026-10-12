<?php

namespace App\Services;

use App\Enums\AutomationType;
use App\Enums\MessageChannel;
use App\Enums\OfferStatus;
use App\Enums\StageCategory;
use App\Models\Activity;
use App\Models\Application;
use App\Models\EmailTemplate;
use App\Models\Offer;
use App\Models\Stage;

/**
 * Moves applications between workflow stages and runs each stage's automations.
 */
class PipelineService
{
    public function __construct(
        private Messenger $messenger,
        private OnboardingService $onboarding,
    ) {}

    public function moveTo(Application $application, Stage $stage, bool $runAutomations = true): Application
    {
        if ($application->stage_id === $stage->id) {
            return $application;
        }

        $from = $application->stage?->name;

        $application->forceFill([
            'stage_id' => $stage->id,
            'stage_entered_at' => now(),
            'position' => (int) Application::where('stage_id', $stage->id)->max('position') + 1,
            'hired_at' => $stage->category === StageCategory::Hired ? now() : null,
            'rejected_at' => $stage->category === StageCategory::Rejected ? now() : null,
        ])->save();

        $application->setRelation('stage', $stage);

        Activity::log($from ? "Moved from {$from} to {$stage->name}" : "Added to {$stage->name}", $application, 'stage');

        if ($runAutomations) {
            $this->runAutomations($application, $stage);
        }

        return $application;
    }

    public function reject(Application $application, bool $runAutomations = true): Application
    {
        $stage = $application->jobOpening->workflow?->stages()->where('category', StageCategory::Rejected->value)->first();

        if ($stage) {
            return $this->moveTo($application, $stage, $runAutomations);
        }

        $application->update(['rejected_at' => now()]);
        Activity::log('Marked as not selected', $application, 'stage');

        return $application;
    }

    public function hire(Application $application): Application
    {
        $stage = $application->jobOpening->workflow?->stages()->where('category', StageCategory::Hired->value)->first();

        if ($stage) {
            $this->moveTo($application, $stage);
        } else {
            $application->update(['hired_at' => now()]);
        }

        // Hired always starts onboarding, even if the stage has no onboarding automation.
        $this->onboarding->startFromApplication($application->fresh(['candidate', 'jobOpening', 'offer']));

        return $application;
    }

    public function runAutomations(Application $application, Stage $stage): void
    {
        $application->loadMissing(['candidate', 'jobOpening']);
        $data = Messenger::mergeData($application);

        foreach ($stage->automations ?? [] as $automation) {
            $type = AutomationType::tryFrom($automation['type'] ?? '');
            if (! $type) {
                continue;
            }

            $template = isset($automation['template_id']) ? EmailTemplate::find($automation['template_id']) : null;
            $subject = EmailTemplate::render($template?->subject ?? $automation['subject'] ?? null, $data);
            $body = EmailTemplate::render($template?->body ?? $automation['body'] ?? null, $data);

            match ($type) {
                AutomationType::Email => $this->messenger->send(
                    $application->candidate, MessageChannel::Email,
                    $body ?: "Hi {$data['first_name']},\n\nYour application for {$data['job_title']} has moved to {$data['stage']}. We'll be in touch soon.\n\n{$data['company']}",
                    $subject ?: "Update on your application: {$data['job_title']}",
                    $application, true,
                ),
                AutomationType::Sms => $application->candidate->phone && $application->candidate->sms_opt_in
                    ? $this->messenger->send($application->candidate, MessageChannel::Sms,
                        $body ?: "Hi {$data['first_name']}, this is {$data['company']} about the {$data['job_title']} role. Reply with a good time to talk!",
                        null, $application, true)
                    : null,
                AutomationType::Questionnaire => $this->messenger->send(
                    $application->candidate, MessageChannel::Email,
                    $body ?: "Hi {$data['first_name']},\n\nPlease complete a short screening questionnaire for {$data['job_title']}. It takes about 5 minutes.\n\n{$data['company']}",
                    $subject ?: 'Next step: screening questionnaire', $application, true,
                ),
                AutomationType::Scorecard => Activity::log('Scorecards requested from the hiring team', $application, 'task'),
                AutomationType::Task => Activity::log('Task: '.($automation['note'] ?? 'Follow up with candidate'), $application, 'task'),
                AutomationType::AutoReject => $this->autoReject($application, (int) ($automation['min_score'] ?? 50)),
                AutomationType::TalentPool => $application->candidate->update(['in_talent_pool' => true]),
                AutomationType::Offer => $this->draftOffer($application),
                AutomationType::BackgroundCheck => Activity::log('Background check ordered (demo provider)', $application, 'task'),
                AutomationType::Onboarding => $this->onboarding->startFromApplication($application->fresh(['candidate', 'jobOpening', 'offer'])),
            };
        }
    }

    private function autoReject(Application $application, int $minScore): void
    {
        if ($application->knocked_out || ($application->score !== null && $application->score < $minScore)) {
            Activity::log("Auto-rejected (score {$application->score}, minimum {$minScore})", $application, 'stage');
            $this->reject($application);
        }
    }

    public function draftOffer(Application $application): Offer
    {
        if ($offer = $application->offer()->first()) {
            return $offer;
        }

        $job = $application->jobOpening;
        $salary = (float) ($job->salary_max && $job->salary_min ? ($job->salary_min + $job->salary_max) / 2 : ($job->salary_min ?: 60000));
        $start = now()->addWeeks(3)->startOfWeek()->toDateString();

        $offer = Offer::create([
            'application_id' => $application->id,
            'salary' => round($salary, $job->salary_period === 'hour' ? 2 : -3),
            'salary_period' => $job->salary_period,
            'start_date' => $start,
            'expires_at' => now()->addDays(7),
            'status' => OfferStatus::Draft,
            'body' => Offer::defaultBody($application, $salary, $job->salary_period, $start),
        ]);

        Activity::log('Offer letter drafted', $application, 'offer');

        return $offer;
    }
}
