<?php

namespace App\Filament\Pages;

use App\Enums\OfferStatus;
use App\Enums\StageCategory;
use App\Models\Application;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Interview;
use App\Models\Offer;
use App\Models\Scorecard;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use UnitEnum;

/** Pre-built recruiting reports: funnel, sources, time to hire, EEO, interviewer activity, onboarding. */
class Reports extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string | UnitEnum | null $navigationGroup = 'Insights';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reports';

    #[Url]
    public string $range = '365';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export applications (CSV)')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->url(fn () => route('reports.export', ['days' => $this->range])),
        ];
    }

    private function from(): Carbon
    {
        return now()->subDays((int) $this->range ?: 365);
    }

    protected function getViewData(): array
    {
        $from = $this->from();
        $apps = Application::with(['stage', 'jobOpening.department'])->where('applied_at', '>=', $from)->get();

        $reached = fn (array $cats) => $apps->filter(fn ($a) => $a->hired_at || in_array($a->stage?->category?->value, $cats, true))->count();
        $funnel = [
            ['Applied', $apps->count(), '#64748b'],
            ['Screened', $reached(['review', 'interview', 'offer', 'hired']) + $apps->filter(fn ($a) => $a->rejected_at && $a->stage_entered_at?->gt($a->applied_at->clone()->addDay()))->count(), '#0ea5e9'],
            ['Interviewed', $reached(['interview', 'offer', 'hired']), '#6366f1'],
            ['Offered', $reached(['offer', 'hired']), '#f59e0b'],
            ['Hired', $apps->whereNotNull('hired_at')->count(), '#10b981'],
        ];

        $sources = $apps->groupBy(fn ($a) => $a->source ?: 'Unknown')->map(fn ($g, $source) => [
            'source' => $source,
            'applications' => $g->count(),
            'interviews' => $g->filter(fn ($a) => in_array($a->stage?->category?->value, ['interview', 'offer', 'hired'], true))->count(),
            'hires' => $g->whereNotNull('hired_at')->count(),
        ])->sortByDesc('applications')->values();

        $hired = $apps->whereNotNull('hired_at');
        $byDept = Department::all()->map(function (Department $d) use ($hired, $apps) {
            $h = $hired->filter(fn ($a) => $a->jobOpening?->department_id === $d->id);

            return [
                'name' => $d->name,
                'color' => $d->color,
                'applications' => $apps->filter(fn ($a) => $a->jobOpening?->department_id === $d->id)->count(),
                'hires' => $h->count(),
                'days' => $h->count() ? (int) round($h->avg(fn ($a) => $a->applied_at->diffInDays($a->hired_at))) : null,
            ];
        })->filter(fn ($r) => $r['applications'] > 0)->values();

        $months = collect(range(11, 0))->map(fn ($m) => now()->startOfMonth()->subMonths($m));
        $monthly = $months->map(fn (Carbon $m) => [
            'label' => $m->format('M'),
            'applications' => Application::whereBetween('applied_at', [$m, $m->clone()->endOfMonth()])->count(),
            'hires' => Application::whereBetween('hired_at', [$m, $m->clone()->endOfMonth()])->count(),
        ]);

        $eeoApps = $apps->filter(fn ($a) => ! empty($a->eeo));
        $eeo = collect(['gender' => 'Gender', 'race' => 'Race / ethnicity', 'veteran' => 'Veteran status'])->map(function ($label, $key) use ($eeoApps) {
            $rows = $eeoApps->groupBy(fn ($a) => $a->eeo[$key] ?? 'Not provided')->map(fn ($g, $value) => [
                'value' => $value,
                'applicants' => $g->count(),
                'hires' => $g->whereNotNull('hired_at')->count(),
            ])->sortByDesc('applicants')->values();

            return ['label' => $label, 'rows' => $rows, 'total' => $rows->sum('applicants')];
        });

        $interviews = Interview::where('scheduled_at', '>=', $from)->get(['id', 'interviewer_ids']);
        $interviewers = User::all()->map(fn (User $u) => [
            'name' => $u->name,
            'interviews' => $interviews->filter(fn ($i) => in_array($u->id, $i->interviewer_ids ?? []))->count(),
            'scorecards' => Scorecard::where('user_id', $u->id)->where('created_at', '>=', $from)->count(),
            'avg' => round((float) Scorecard::where('user_id', $u->id)->avg('rating'), 1),
        ])->filter(fn ($r) => $r['interviews'] || $r['scorecards'])->sortByDesc('interviews')->values();

        $offers = Offer::where('created_at', '>=', $from)->get();
        $decided = $offers->whereIn('status', [OfferStatus::Signed, OfferStatus::Declined])->count();

        $employees = Employee::with('tasks')->get();
        $onboardingDone = $employees->filter(fn ($e) => $e->tasks->isNotEmpty() && $e->tasks->where('assignee.value', 'new_hire')->every(fn ($t) => $t->completed_at && $t->completed_at->lte($e->start_date->clone()->endOfDay())))->count();

        return [
            'kpis' => [
                'applications' => $apps->count(),
                'hires' => $hired->count(),
                'time_to_hire' => $hired->count() ? (int) round($hired->avg(fn ($a) => $a->applied_at->diffInDays($a->hired_at))) : null,
                'acceptance' => $decided ? (int) round($offers->where('status', OfferStatus::Signed)->count() / $decided * 100) : null,
                'onboarding' => $employees->count() ? (int) round($onboardingDone / $employees->count() * 100) : null,
                'per_hire' => $hired->count() ? (int) round($apps->count() / $hired->count()) : null,
            ],
            'funnel' => $funnel,
            'sources' => $sources,
            'byDept' => $byDept,
            'monthly' => $monthly,
            'monthlyMax' => max($monthly->max('applications'), 1),
            'eeo' => $eeo,
            'eeoTotal' => $eeoApps->count(),
            'interviewers' => $interviewers,
            'stageCategories' => StageCategory::cases(),
        ];
    }
}
