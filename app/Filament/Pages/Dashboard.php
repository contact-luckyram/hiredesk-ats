<?php

namespace App\Filament\Pages;

use App\Enums\InterviewStatus;
use App\Enums\JobStatus;
use App\Enums\OfferStatus;
use App\Enums\StageCategory;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Employee;
use App\Models\Interview;
use App\Models\JobOpening;
use App\Models\Offer;
use App\Support\Svg;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    public function getTitle(): string | Htmlable
    {
        return 'Dashboard';
    }

    public function getHeading(): string | Htmlable
    {
        return '';
    }

    protected function getViewData(): array
    {
        $now = now();
        $from30 = $now->clone()->subDays(30);
        $from60 = $now->clone()->subDays(60);

        $newApplicants = Application::where('applied_at', '>=', $from30)->count();
        $prevApplicants = Application::whereBetween('applied_at', [$from60, $from30])->count();

        $hires = Application::whereNotNull('hired_at')->where('hired_at', '>=', $now->clone()->subDays(180))->get(['applied_at', 'hired_at']);
        $timeToHire = $hires->count() ? (int) round($hires->avg(fn ($a) => $a->applied_at->diffInDays($a->hired_at))) : null;

        $offersDecided = Offer::whereIn('status', [OfferStatus::Signed->value, OfferStatus::Declined->value])->count();
        $acceptance = $offersDecided ? (int) round(Offer::where('status', OfferStatus::Signed->value)->count() / $offersDecided * 100) : null;

        $weeks = collect(range(11, 0))->map(fn (int $w) => $now->clone()->startOfWeek()->subWeeks($w));
        $weekly = $weeks->map(fn (Carbon $start) => Application::whereBetween('applied_at', [$start, $start->clone()->endOfWeek()])->count())->all();
        $weeklyHires = $weeks->map(fn (Carbon $start) => Application::whereBetween('hired_at', [$start, $start->clone()->endOfWeek()])->count())->all();

        $daily = collect(range(13, 0))->map(fn (int $d) => Application::whereDate('applied_at', $now->clone()->subDays($d))->count())->all();

        $active = Application::active()->with('stage')->get();
        $funnel = collect(StageCategory::cases())
            ->reject(fn ($c) => $c === StageCategory::Rejected)
            ->map(fn (StageCategory $c) => [
                'label' => $c->getLabel(),
                'color' => $c->hex(),
                'count' => $c === StageCategory::Hired
                    ? Application::whereNotNull('hired_at')->where('hired_at', '>=', $from30)->count()
                    : $active->filter(fn ($a) => $a->stage?->category === $c)->count(),
            ])->values();

        $sources = Application::where('applied_at', '>=', $now->clone()->subDays(90))
            ->selectRaw('source, count(*) as total')->groupBy('source')->orderByDesc('total')->pluck('total', 'source')->all();

        $interviewsToday = Interview::with(['application.candidate', 'application.jobOpening'])
            ->whereDate('scheduled_at', today())->orderBy('scheduled_at')->get();
        $upcoming = Interview::with(['application.candidate', 'application.jobOpening'])
            ->where('scheduled_at', '>=', $now)->where('status', InterviewStatus::Scheduled->value)
            ->orderBy('scheduled_at')->take(6)->get();

        $overSla = $active->filter->isOverSla()->sortByDesc(fn ($a) => $a->daysInStage())->take(6)
            ->each->load(['candidate', 'jobOpening']);

        $jobs = JobOpening::published()->with(['department', 'location', 'workflow.stages'])
            ->withCount(['applications as applicants_count' => fn ($q) => $q->whereNull('rejected_at')])
            ->orderByDesc('is_featured')->orderByDesc('applicants_count')->take(7)->get();
        $jobStageCounts = Application::active()->whereIn('job_opening_id', $jobs->pluck('id'))
            ->join('stages', 'stages.id', '=', 'applications.stage_id')
            ->selectRaw('job_opening_id, stages.category, count(*) as total')
            ->groupBy('job_opening_id', 'stages.category')->get()
            ->groupBy('job_opening_id');

        $newHires = Employee::with('tasks')->where('status', 'onboarding')->orderBy('start_date')->take(5)->get();

        return [
            'user' => auth()->user(),
            'greeting' => match (true) {
                $now->hour < 12 => 'Good morning',
                $now->hour < 17 => 'Good afternoon',
                default => 'Good evening',
            },
            'stats' => [
                'open_jobs' => JobOpening::published()->count(),
                'openings' => (int) JobOpening::published()->sum('openings'),
                'pending_jobs' => JobOpening::where('status', JobStatus::PendingApproval->value)->count(),
                'new_applicants' => $newApplicants,
                'applicant_change' => $prevApplicants ? (int) round(($newApplicants - $prevApplicants) / $prevApplicants * 100) : null,
                'interviews_week' => Interview::whereBetween('scheduled_at', [$now->clone()->startOfWeek(), $now->clone()->endOfWeek()])->count(),
                'interviews_today' => $interviewsToday->count(),
                'time_to_hire' => $timeToHire,
                'acceptance' => $acceptance,
                'offers_out' => Offer::where('status', OfferStatus::Sent->value)->count(),
                'over_sla' => $active->filter->isOverSla()->count(),
                'onboarding' => Employee::where('status', 'onboarding')->count(),
            ],
            'daily' => Svg::series($daily, 120, 36, 3),
            'weekly' => [
                'labels' => $weeks->map(fn ($w) => $w->format('M j'))->all(),
                'applicants' => Svg::series($weekly, 640, 200, 12),
                'hires' => Svg::series($weeklyHires, 640, 200, 12, max(max($weekly), 1)),
                'values' => $weekly,
                'hireValues' => $weeklyHires,
                'max' => max(max($weekly), 1),
                'total' => array_sum($weekly),
            ],
            'funnel' => $funnel,
            'funnelMax' => max($funnel->max('count'), 1),
            'sources' => Svg::donut($sources, 52),
            'sourceTotal' => array_sum($sources),
            'interviewsToday' => $interviewsToday,
            'upcoming' => $upcoming,
            'overSla' => $overSla,
            'jobs' => $jobs,
            'jobStageCounts' => $jobStageCounts,
            'newHires' => $newHires,
            'activities' => Activity::with(['application.candidate', 'application.jobOpening', 'user', 'employee'])->latest()->take(8)->get(),
        ];
    }
}
