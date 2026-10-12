<?php

namespace App\Filament\Pages;

use App\Enums\JobStatus;
use App\Filament\Resources\Workflows\WorkflowResource;
use App\Models\Application;
use App\Models\JobOpening;
use App\Models\Stage;
use App\Services\PipelineService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Url;
use UnitEnum;

/** Kanban board: drag candidates between a job's workflow stages (stage automations run on drop). */
class Pipeline extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static string | UnitEnum | null $navigationGroup = 'Recruit';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Pipeline';

    protected string $view = 'filament.pages.pipeline';

    #[Url]
    public ?int $job = null;

    #[Url]
    public string $search = '';

    #[Url]
    public bool $showRejected = false;

    public function mount(): void
    {
        if (! $this->job || ! JobOpening::whereKey($this->job)->exists()) {
            $this->job = JobOpening::published()->orderByDesc('is_featured')->withCount('applications')->orderByDesc('applications_count')->value('id')
                ?? JobOpening::value('id');
        }
    }

    public function getTitle(): string | Htmlable
    {
        return 'Hiring pipeline';
    }

    public function getSubheading(): ?string
    {
        $job = $this->currentJob();

        return $job ? "{$job->title} · {$job->locationLabel()} · {$job->workflow?->name} workflow" : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('stages')
                ->label('Edit stages')
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->color('gray')
                ->url(fn () => ($wf = $this->currentJob()?->workflow) ? WorkflowResource::getUrl('edit', ['record' => $wf]) : WorkflowResource::getUrl()),
            Action::make('careers')
                ->label('Job page')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->visible(fn () => $this->currentJob()?->status === JobStatus::Published)
                ->url(fn () => route('careers.show', $this->currentJob()), shouldOpenInNewTab: true),
        ];
    }

    public function currentJob(): ?JobOpening
    {
        return $this->job ? JobOpening::with(['workflow.stages', 'location'])->find($this->job) : null;
    }

    public function moveApplication(int $applicationId, int $stageId): void
    {
        $application = Application::with(['candidate', 'jobOpening.workflow', 'stage'])->findOrFail($applicationId);
        $stage = Stage::where('workflow_id', $application->jobOpening->workflow_id)->findOrFail($stageId);

        if ($application->stage_id === $stage->id) {
            return;
        }

        app(PipelineService::class)->moveTo($application, $stage);

        $automations = count($stage->automations ?? []);
        Notification::make()
            ->title("{$application->candidate->name} moved to {$stage->name}")
            ->body($automations ? "{$automations} stage ".str('automation')->plural($automations).' ran.' : null)
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $job = $this->currentJob();
        $stages = $job?->workflow?->stages ?? collect();

        $applications = $job
            ? Application::with(['candidate', 'stage'])->withCount(['interviews', 'scorecards'])
                ->where('job_opening_id', $job->id)
                ->when($this->search, fn ($q) => $q->whereHas('candidate', fn ($c) => $c
                    ->where('first_name', 'like', "%{$this->search}%")
                    ->orWhere('last_name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")))
                ->orderByDesc('score')
                ->get()
                ->groupBy('stage_id')
            : collect();

        $visible = $stages->filter(fn (Stage $s) => $this->showRejected || ! $s->category->isClosed() || $s->category->value === 'hired');

        return [
            'jobOptions' => JobOpening::orderByRaw("case when status = 'published' then 0 else 1 end")->orderBy('title')->get(['id', 'title', 'status']),
            'currentJob' => $job,
            'stages' => $visible->values(),
            'allStages' => $stages,
            'applications' => $applications,
            'totals' => [
                'active' => $applications->flatten()->filter(fn ($a) => ! $a->stage?->category->isClosed())->count(),
                'rejected' => $applications->flatten()->filter(fn ($a) => $a->stage?->category->value === 'rejected')->count(),
            ],
        ];
    }
}
