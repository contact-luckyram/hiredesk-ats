<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use App\Enums\MessageChannel;
use App\Enums\OfferStatus;
use App\Enums\Recommendation;
use App\Enums\StageCategory;
use App\Filament\Actions\CandidateActions;
use App\Filament\Pages\Pipeline;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Candidates\CandidateResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Offers\OfferResource;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Interview;
use App\Models\Note;
use App\Models\Offer;
use App\Models\OnboardingTemplate;
use App\Models\Scorecard;
use App\Models\User;
use App\Services\Messenger;
use App\Services\OnboardingService;
use App\Services\PipelineService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

/** Full candidate profile for one application: stage bar, screening, resume, scorecards, messages, offer. */
class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    protected string $view = 'filament.applications.view';

    public string $note = '';

    public function getTitle(): string | Htmlable
    {
        return $this->appRecord()->candidate->name;
    }

    public function getBreadcrumbs(): array
    {
        return [
            Pipeline::getUrl(['job' => $this->appRecord()->job_opening_id]) => $this->appRecord()->jobOpening->title,
            '#' => $this->appRecord()->candidate->name,
        ];
    }

    public function appRecord(): Application
    {
        /** @var Application */
        return $this->getRecord();
    }

    public function moveTo(int $stageId): void
    {
        $stage = $this->appRecord()->jobOpening->workflow->stages->firstWhere('id', $stageId);
        if (! $stage) {
            return;
        }

        if ($stage->category === StageCategory::Hired) {
            $this->mountAction('hire');

            return;
        }

        app(PipelineService::class)->moveTo($this->appRecord(), $stage);
        $this->getRecord()->refresh();
        Notification::make()->title("Moved to {$stage->name}")->body(count($stage->automations ?? []) ? 'Stage automations ran.' : null)->success()->send();
    }

    public function setRating(int $rating): void
    {
        $this->appRecord()->update(['rating' => $rating]);
        Activity::log("Rated {$rating} of 5", $this->appRecord(), 'rating');
    }

    public function addNote(): void
    {
        $this->validate(['note' => 'required|string|max:5000']);
        Note::create(['application_id' => $this->appRecord()->id, 'user_id' => auth()->id(), 'body' => $this->note]);
        Activity::log('Added a note', $this->appRecord(), 'note');
        $this->note = '';
        Notification::make()->title('Note added')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        $resolve = fn () => [$this->appRecord()->candidate, $this->appRecord()];

        return [
            CandidateActions::message(MessageChannel::Email, $resolve),
            CandidateActions::message(MessageChannel::Sms, $resolve),
            $this->scheduleAction(),
            ActionGroup::make([
                $this->scorecardAction(),
                $this->offerAction(),
                Action::make('profile')->label('Candidate profile')->icon(Heroicon::OutlinedUser)
                    ->url(fn () => CandidateResource::getUrl('view', ['record' => $this->appRecord()->candidate_id])),
                Action::make('resume')->label('Download resume')->icon(Heroicon::OutlinedArrowDownTray)
                    ->visible(fn () => filled($this->appRecord()->candidate->resume_path))
                    ->url(fn () => route('resumes.download', $this->appRecord()->candidate_id)),
                $this->rejectAction(),
            ])->label('More')->icon(Heroicon::OutlinedEllipsisVertical)->button()->color('gray'),
            $this->hireAction(),
        ];
    }

    private function scheduleAction(): Action
    {
        return Action::make('schedule')
            ->label('Schedule')
            ->icon(Heroicon::OutlinedCalendarDays)
            ->color('gray')
            ->modalHeading('Schedule interview')
            ->schema([
                TextInput::make('title')->default('Interview')->required(),
                Select::make('type')->options(InterviewType::class)->default(InterviewType::Video->value)->required()->native(false),
                DateTimePicker::make('scheduled_at')->label('Date & time')->required()->seconds(false)->minutesStep(15)->native(false)
                    ->default(now()->addDays(2)->setTime(10, 0)),
                Select::make('duration_minutes')->label('Length')->options([15 => '15 min', 30 => '30 min', 45 => '45 min', 60 => '1 hour', 90 => '90 min'])->default(45)->native(false),
                TextInput::make('location')->placeholder('Video link, room or phone'),
                Select::make('interviewer_ids')->label('Interviewers')->multiple()->options(fn () => User::orderBy('name')->pluck('name', 'id'))->default(fn () => [auth()->id()]),
                Toggle::make('notify')->label('Email the candidate a calendar invite')->default(true),
            ])
            ->action(function (array $data) {
                $interview = Interview::create([
                    'application_id' => $this->appRecord()->id,
                    'title' => $data['title'],
                    'type' => $data['type'],
                    'scheduled_at' => $data['scheduled_at'],
                    'duration_minutes' => $data['duration_minutes'],
                    'location' => $data['location'],
                    'interviewer_ids' => array_map('intval', $data['interviewer_ids'] ?? []),
                    'status' => InterviewStatus::Scheduled,
                ]);
                Activity::log("{$interview->title} scheduled for ".$interview->scheduled_at->format('D M j, g:i A'), $this->appRecord(), 'interview');

                if ($data['notify']) {
                    app(Messenger::class)->send(
                        $this->appRecord()->candidate, MessageChannel::Email,
                        "Hi {$this->appRecord()->candidate->first_name},\n\nYour {$interview->title} for {$this->appRecord()->jobOpening->title} is confirmed for "
                        .$interview->scheduled_at->format('l, F j \a\t g:i A')." ({$interview->duration_minutes} minutes).\n\nLocation: ".($interview->location ?: 'details to follow')
                        ."\n\nA calendar invite is attached.\n\n".config('hiredesk.company'),
                        "Interview confirmed: {$this->appRecord()->jobOpening->title}",
                        $this->appRecord(),
                    );
                }

                Notification::make()->title('Interview scheduled')->success()
                    ->actions([Action::make('ics')->label('Download invite (.ics)')->url(route('interviews.ics', $interview))])
                    ->send();
            });
    }

    private function scorecardAction(): Action
    {
        return Action::make('scorecard')
            ->label('Add scorecard')
            ->icon(Heroicon::OutlinedStar)
            ->schema([
                Select::make('interview_id')->label('Interview')
                    ->options(fn () => $this->appRecord()->interviews->mapWithKeys(fn ($i) => [$i->id => $i->title.' · '.$i->scheduled_at->format('M j')]))
                    ->placeholder('General feedback'),
                TextInput::make('round')->default('Interview'),
                Radio::make('rating')->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])->inline()->required()->default(4),
                Select::make('recommendation')->options(Recommendation::class)->required()->default(Recommendation::Hire->value)->native(false),
                Textarea::make('strengths')->rows(2),
                Textarea::make('concerns')->rows(2),
            ])
            ->action(function (array $data) {
                Scorecard::create($data + ['application_id' => $this->appRecord()->id, 'user_id' => auth()->id()]);
                Activity::log('Scorecard submitted: '.Recommendation::from($data['recommendation'] instanceof Recommendation ? $data['recommendation']->value : $data['recommendation'])->getLabel(), $this->appRecord(), 'scorecard');
                Notification::make()->title('Scorecard saved')->success()->send();
            });
    }

    private function offerAction(): Action
    {
        return Action::make('offer')
            ->label(fn () => $this->appRecord()->offer ? 'Open offer' : 'Create offer')
            ->icon(Heroicon::OutlinedDocumentText)
            ->url(fn () => $this->appRecord()->offer ? OfferResource::getUrl('edit', ['record' => $this->appRecord()->offer]) : null)
            ->schema(fn () => $this->appRecord()->offer ? [] : [
                TextInput::make('salary')->numeric()->required()->prefix('$')
                    ->default(fn () => ($j = $this->appRecord()->jobOpening) && $j->salary_min ? round(($j->salary_min + ($j->salary_max ?: $j->salary_min)) / 2, $j->salary_period === 'hour' ? 2 : -3) : null),
                Select::make('salary_period')->options(['year' => 'Per year', 'hour' => 'Per hour'])->default(fn () => $this->appRecord()->jobOpening->salary_period)->native(false),
                DatePicker::make('start_date')->required()->native(false)->default(now()->addWeeks(3)->startOfWeek()),
                DatePicker::make('expires_at')->label('Offer expires')->native(false)->default(now()->addWeek()),
            ])
            ->action(function (array $data) {
                $offer = Offer::create([
                    'application_id' => $this->appRecord()->id,
                    'salary' => $data['salary'],
                    'salary_period' => $data['salary_period'],
                    'start_date' => $data['start_date'],
                    'expires_at' => $data['expires_at'],
                    'status' => OfferStatus::Draft,
                    'body' => Offer::defaultBody($this->appRecord(), (float) $data['salary'], $data['salary_period'], (string) $data['start_date']),
                ]);
                Activity::log('Offer letter drafted', $this->appRecord(), 'offer');
                $this->redirect(OfferResource::getUrl('edit', ['record' => $offer]));
            });
    }

    private function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Not selected')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn () => ! $this->appRecord()->rejected_at)
            ->requiresConfirmation()
            ->modalDescription('Moves the candidate to the "not selected" stage.')
            ->schema([Toggle::make('notify')->label('Send the stage\'s rejection email and add to talent pool')->default(true)])
            ->action(function (array $data) {
                app(PipelineService::class)->reject($this->appRecord(), (bool) $data['notify']);
                $this->getRecord()->refresh();
                Notification::make()->title('Marked as not selected')->success()->send();
            });
    }

    private function hireAction(): Action
    {
        return Action::make('hire')
            ->label('Hire')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->visible(fn () => ! $this->appRecord()->hired_at)
            ->modalHeading(fn () => 'Hire '.$this->appRecord()->candidate->name)
            ->modalDescription('Creates the employee record, onboarding checklist and new-hire portal.')
            ->schema([
                DatePicker::make('start_date')->required()->native(false)
                    ->default(fn () => $this->appRecord()->offer?->start_date ?? now()->addWeeks(2)->startOfWeek()),
                Select::make('onboarding_template_id')->label('Onboarding template')
                    ->options(fn () => OnboardingTemplate::pluck('name', 'id'))
                    ->default(fn () => OnboardingTemplate::default()?->id)
                    ->required()
                    ->native(false),
            ])
            ->action(function (array $data) {
                $employee = app(OnboardingService::class)->startFromApplication(
                    $this->appRecord(), OnboardingTemplate::find($data['onboarding_template_id']), Carbon::parse($data['start_date']),
                );
                app(PipelineService::class)->hire($this->appRecord());
                $this->getRecord()->refresh();

                Notification::make()->title("{$employee->name} hired 🎉")->body('Onboarding started and the new-hire portal is ready.')->success()
                    ->actions([Action::make('open')->label('Open onboarding')->url(EmployeeResource::getUrl('view', ['record' => $employee]))])
                    ->send();
            });
    }

    protected function getViewData(): array
    {
        $app = $this->appRecord()->load(['candidate', 'jobOpening.workflow.stages', 'jobOpening.questionnaire', 'stage', 'interviews', 'scorecards.user', 'notes.user', 'messages', 'activities.user', 'offer', 'employee']);

        return [
            'app' => $app,
            'stages' => $app->jobOpening->workflow?->stages ?? collect(),
            'otherApplications' => $app->candidate->applications()->with(['jobOpening', 'stage'])->whereKeyNot($app->id)->get(),
        ];
    }
}
