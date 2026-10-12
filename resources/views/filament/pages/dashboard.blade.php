@php
    use App\Filament\Pages\Pipeline;
    use App\Filament\Resources\Applications\ApplicationResource;
    use App\Filament\Resources\Employees\EmployeeResource;
    use App\Filament\Resources\Interviews\InterviewResource;
    use App\Filament\Resources\JobOpenings\JobOpeningResource;
    use App\Filament\Resources\Candidates\CandidateResource;
@endphp

<x-filament-panels::page class="hd-dashboard">
    {{-- Hero --}}
    <section class="hd-hero">
        <div class="hd-hero-glow"></div>
        <div class="hd-hero-body">
            <p class="hd-hero-eyebrow">{{ now()->format('l, F j') }} · {{ config('hiredesk.company') }}</p>
            <h1 class="hd-hero-title">{{ $greeting }}, {{ \Illuminate\Support\Str::before($user->name, ' ') }} 👋</h1>
            <p class="hd-hero-text">
                You have <b>{{ $stats['interviews_today'] }} {{ \Illuminate\Support\Str::plural('interview', $stats['interviews_today']) }}</b> today,
                <b>{{ $stats['over_sla'] }}</b> {{ \Illuminate\Support\Str::plural('candidate', $stats['over_sla']) }} waiting past their stage SLA
                and <b>{{ $stats['offers_out'] }}</b> {{ \Illuminate\Support\Str::plural('offer', $stats['offers_out']) }} out for signature.
            </p>
            <div class="hd-hero-actions">
                <a href="{{ JobOpeningResource::getUrl('create') }}" class="hd-btn hd-btn-white">
                    <x-filament::icon icon="heroicon-m-plus" class="hd-i"/> Post a job
                </a>
                <a href="{{ Pipeline::getUrl() }}" class="hd-btn hd-btn-ghost">
                    <x-filament::icon icon="heroicon-m-view-columns" class="hd-i"/> Open pipeline
                </a>
                <a href="{{ CandidateResource::getUrl('create') }}" class="hd-btn hd-btn-ghost">
                    <x-filament::icon icon="heroicon-m-user-plus" class="hd-i"/> Add candidate
                </a>
                <a href="{{ route('careers.index') }}" target="_blank" class="hd-btn hd-btn-ghost">
                    <x-filament::icon icon="heroicon-m-globe-alt" class="hd-i"/> Careers site
                </a>
            </div>
        </div>
        <div class="hd-hero-stats">
            <div class="hd-hero-stat">
                <span>Open roles</span>
                <strong>{{ $stats['open_jobs'] }}</strong>
                <small>{{ $stats['openings'] }} openings to fill</small>
            </div>
            <div class="hd-hero-stat">
                <span>New hires onboarding</span>
                <strong>{{ $stats['onboarding'] }}</strong>
                <small>paperless, before day one</small>
            </div>
        </div>
    </section>

    {{-- KPI cards --}}
    <section class="hd-kpis">
        <a href="{{ ApplicationResource::getUrl() }}" class="hd-card hd-kpi">
            <div class="hd-kpi-top"><span class="hd-kpi-icon hd-tint-indigo"><x-filament::icon icon="heroicon-o-inbox-arrow-down"/></span>New applicants · 30 days</div>
            <div class="hd-kpi-row">
                <strong>{{ number_format($stats['new_applicants']) }}</strong>
                <svg viewBox="0 0 120 36" class="hd-spark" preserveAspectRatio="none" aria-hidden="true">
                    <path d="{{ $daily['area'] }}" class="hd-spark-area"/><path d="{{ $daily['line'] }}" class="hd-spark-line"/>
                </svg>
            </div>
            @if ($stats['applicant_change'] !== null)
                <span class="hd-trend {{ $stats['applicant_change'] >= 0 ? 'up' : 'down' }}">{{ $stats['applicant_change'] >= 0 ? '▲' : '▼' }} {{ abs($stats['applicant_change']) }}% vs previous 30 days</span>
            @endif
        </a>
        <a href="{{ InterviewResource::getUrl() }}" class="hd-card hd-kpi">
            <div class="hd-kpi-top"><span class="hd-kpi-icon hd-tint-sky"><x-filament::icon icon="heroicon-o-calendar-days"/></span>Interviews this week</div>
            <div class="hd-kpi-row"><strong>{{ $stats['interviews_week'] }}</strong></div>
            <span class="hd-trend neutral">{{ $stats['interviews_today'] }} today</span>
        </a>
        <div class="hd-card hd-kpi">
            <div class="hd-kpi-top"><span class="hd-kpi-icon hd-tint-emerald"><x-filament::icon icon="heroicon-o-clock"/></span>Avg. time to hire</div>
            <div class="hd-kpi-row"><strong>{{ $stats['time_to_hire'] ?? '—' }}<small> days</small></strong></div>
            <span class="hd-trend neutral">last 6 months, applied → hired</span>
        </div>
        <div class="hd-card hd-kpi">
            <div class="hd-kpi-top"><span class="hd-kpi-icon hd-tint-amber"><x-filament::icon icon="heroicon-o-document-check"/></span>Offer acceptance</div>
            <div class="hd-kpi-row"><strong>{{ $stats['acceptance'] !== null ? $stats['acceptance'].'%' : '—' }}</strong></div>
            <span class="hd-trend neutral">{{ $stats['offers_out'] }} awaiting signature</span>
        </div>
        <a href="{{ Pipeline::getUrl() }}" class="hd-card hd-kpi">
            <div class="hd-kpi-top"><span class="hd-kpi-icon hd-tint-rose"><x-filament::icon icon="heroicon-o-exclamation-triangle"/></span>Past stage SLA</div>
            <div class="hd-kpi-row"><strong>{{ $stats['over_sla'] }}</strong></div>
            <span class="hd-trend {{ $stats['over_sla'] ? 'down' : 'up' }}">{{ $stats['over_sla'] ? 'needs follow-up' : 'all on track' }}</span>
        </a>
    </section>

    {{-- Charts --}}
    <section class="hd-grid hd-grid-2-1">
        <div class="hd-card">
            <div class="hd-card-head">
                <div>
                    <h3>Applications &amp; hires</h3>
                    <p>Last 12 weeks · {{ number_format($weekly['total']) }} applications</p>
                </div>
                <div class="hd-legend"><span><i style="background:#6366f1"></i>Applications</span><span><i style="background:#10b981"></i>Hires</span></div>
            </div>
            <div class="hd-chart">
                <svg viewBox="0 0 640 200" preserveAspectRatio="none" class="hd-area-chart" role="img" aria-label="Applications per week">
                    <defs>
                        <linearGradient id="hdArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#6366f1" stop-opacity=".35"/><stop offset="100%" stop-color="#6366f1" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    @foreach ([0.25, 0.5, 0.75] as $g)
                        <line x1="0" x2="640" y1="{{ 12 + (176 * $g) }}" y2="{{ 12 + (176 * $g) }}" class="hd-gridline"/>
                    @endforeach
                    <path d="{{ $weekly['applicants']['area'] }}" fill="url(#hdArea)"/>
                    <path d="{{ $weekly['applicants']['line'] }}" class="hd-line hd-line-indigo"/>
                    <path d="{{ $weekly['hires']['area'] }}" class="hd-area-emerald"/>
                    <path d="{{ $weekly['hires']['line'] }}" class="hd-line hd-line-emerald"/>
                    @foreach ($weekly['applicants']['points'] as $i => $p)
                        <circle cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="3.5" class="hd-dot"><title>{{ $weekly['labels'][$i] }}: {{ $weekly['values'][$i] }} applications, {{ $weekly['hireValues'][$i] }} hires</title></circle>
                    @endforeach
                </svg>
                <div class="hd-xlabels">
                    @foreach ($weekly['labels'] as $i => $label)
                        <span @class(['hd-hide-sm' => $i % 2])>{{ $label }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="hd-card">
            <div class="hd-card-head"><div><h3>Pipeline snapshot</h3><p>Active candidates by stage</p></div></div>
            <div class="hd-funnel">
                @foreach ($funnel as $row)
                    <div class="hd-funnel-row">
                        <span class="hd-funnel-label"><i style="background: {{ $row['color'] }}"></i>{{ $row['label'] }}</span>
                        <div class="hd-funnel-bar"><span style="width: {{ max(3, $row['count'] / $funnelMax * 100) }}%; background: {{ $row['color'] }}"></span></div>
                        <b>{{ $row['count'] }}</b>
                    </div>
                @endforeach
                <p class="hd-muted hd-small">Hired = last 30 days</p>
            </div>
        </div>
    </section>

    <section class="hd-grid hd-grid-3">
        {{-- Interviews --}}
        <div class="hd-card">
            <div class="hd-card-head"><div><h3>Upcoming interviews</h3><p>{{ $stats['interviews_today'] }} today</p></div>
                <a href="{{ InterviewResource::getUrl() }}" class="hd-link">View all</a></div>
            <ul class="hd-list">
                @forelse ($upcoming as $interview)
                    <li>
                        <a href="{{ ApplicationResource::getUrl('view', ['record' => $interview->application_id]) }}" class="hd-list-item">
                            <span class="hd-date-badge @if($interview->scheduled_at->isToday()) is-today @endif">
                                <small>{{ $interview->scheduled_at->format('D') }}</small>{{ $interview->scheduled_at->format('j') }}
                            </span>
                            <span class="hd-list-main">
                                <b>{{ $interview->application->candidate->name }}</b>
                                <small>{{ $interview->title }} · {{ $interview->application->jobOpening->title }}</small>
                            </span>
                            <span class="hd-time">{{ $interview->scheduled_at->format('g:i A') }}</span>
                        </a>
                    </li>
                @empty
                    <li class="hd-empty">No interviews scheduled.</li>
                @endforelse
            </ul>
        </div>

        {{-- Needs attention --}}
        <div class="hd-card">
            <div class="hd-card-head"><div><h3>Needs attention</h3><p>Waiting longer than the stage SLA</p></div></div>
            <ul class="hd-list">
                @forelse ($overSla as $app)
                    <li>
                        <a href="{{ ApplicationResource::getUrl('view', ['record' => $app]) }}" class="hd-list-item">
                            <span class="hd-avatar" style="--c: {{ $app->stage->color }}">{{ $app->candidate->initials() }}</span>
                            <span class="hd-list-main">
                                <b>{{ $app->candidate->name }}</b>
                                <small>{{ $app->stage->name }} · {{ $app->jobOpening->title }}</small>
                            </span>
                            <span class="hd-pill hd-pill-amber">{{ $app->daysInStage() }}d / {{ $app->stage->sla_days }}d</span>
                        </a>
                    </li>
                @empty
                    <li class="hd-empty">Everyone is on track. 🎉</li>
                @endforelse
            </ul>
        </div>

        {{-- Activity --}}
        <div class="hd-card">
            <div class="hd-card-head"><div><h3>Recent activity</h3><p>Across all jobs</p></div></div>
            <ul class="hd-timeline">
                @foreach ($activities as $activity)
                    <li>
                        <span class="hd-timeline-dot hd-type-{{ $activity->type }}"></span>
                        <div>
                            <p>
                                @if ($activity->application)
                                    <a href="{{ ApplicationResource::getUrl('view', ['record' => $activity->application_id]) }}"><b>{{ $activity->application->candidate->name }}</b></a> —
                                @elseif ($activity->employee)
                                    <b>{{ $activity->employee->name }}</b> —
                                @endif
                                {{ $activity->description }}
                            </p>
                            <small>{{ $activity->created_at->diffForHumans() }}@if($activity->user) · {{ $activity->user->name }}@endif</small>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="hd-grid hd-grid-2-1">
        {{-- Open jobs --}}
        <div class="hd-card hd-card-flush">
            <div class="hd-card-head hd-pad"><div><h3>Open jobs</h3><p>Where candidates are in each pipeline</p></div>
                <a href="{{ JobOpeningResource::getUrl() }}" class="hd-link">All jobs</a></div>
            <div class="hd-table-wrap">
                <table class="hd-table">
                    <thead><tr><th>Job</th><th class="hd-hide-sm">Location</th><th>Pipeline</th><th class="hd-num">Active</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($jobs as $job)
                        @php $counts = ($jobStageCounts[$job->id] ?? collect())->pluck('total', 'category'); $sum = max($counts->sum(), 1); @endphp
                        <tr>
                            <td>
                                <a href="{{ Pipeline::getUrl(['job' => $job->id]) }}" class="hd-job-title">{{ $job->title }}</a>
                                <span class="hd-chip" style="--c: {{ $job->department?->color ?? '#64748b' }}">{{ $job->department?->name }}</span>
                                @if ($job->is_featured)<span class="hd-chip hd-chip-hot">Hot</span>@endif
                            </td>
                            <td class="hd-hide-sm hd-muted">{{ $job->locationLabel() }}</td>
                            <td class="hd-stackbar-cell">
                                <div class="hd-stackbar">
                                    @foreach (['applied', 'review', 'interview', 'offer'] as $cat)
                                        @if (($counts[$cat] ?? 0) > 0)
                                            <span style="width: {{ $counts[$cat] / $sum * 100 }}%; background: {{ \App\Enums\StageCategory::from($cat)->hex() }}" title="{{ \App\Enums\StageCategory::from($cat)->getLabel() }}: {{ $counts[$cat] }}"></span>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                            <td class="hd-num"><b>{{ $counts->sum() }}</b></td>
                            <td class="hd-num"><a href="{{ Pipeline::getUrl(['job' => $job->id]) }}" class="hd-link">Pipeline →</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="hd-legend hd-pad">
                @foreach (['applied', 'review', 'interview', 'offer'] as $cat)
                    <span><i style="background: {{ \App\Enums\StageCategory::from($cat)->hex() }}"></i>{{ \App\Enums\StageCategory::from($cat)->getLabel() }}</span>
                @endforeach
            </div>
        </div>

        <div class="hd-stack">
            {{-- Sources --}}
            <div class="hd-card">
                <div class="hd-card-head"><div><h3>Top sources</h3><p>Applications, last 90 days</p></div></div>
                <div class="hd-donut-wrap">
                    @php $palette = ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#a855f7', '#ef4444', '#64748b']; @endphp
                    <svg viewBox="0 0 140 140" class="hd-donut" role="img" aria-label="Applications by source">
                        <circle cx="70" cy="70" r="52" class="hd-donut-track"/>
                        @foreach ($sources as $i => $seg)
                            <circle cx="70" cy="70" r="52" fill="none" stroke="{{ $palette[$i % 7] }}" stroke-width="18" stroke-dasharray="{{ $seg['dash'] }}" stroke-dashoffset="{{ $seg['offset'] }}" transform="rotate(-90 70 70)"><title>{{ $seg['label'] }}: {{ $seg['value'] }}</title></circle>
                        @endforeach
                        <text x="70" y="68" text-anchor="middle" class="hd-donut-total">{{ $sourceTotal }}</text>
                        <text x="70" y="86" text-anchor="middle" class="hd-donut-caption">applications</text>
                    </svg>
                    <ul class="hd-donut-legend">
                        @foreach (array_slice($sources, 0, 6) as $i => $seg)
                            <li><i style="background: {{ $palette[$i % 7] }}"></i>{{ $seg['label'] ?: 'Unknown' }}<b>{{ $seg['percent'] }}%</b></li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- New hires --}}
            <div class="hd-card">
                <div class="hd-card-head"><div><h3>New hires onboarding</h3><p>Paperwork before day one</p></div>
                    <a href="{{ EmployeeResource::getUrl() }}" class="hd-link">All</a></div>
                <ul class="hd-list">
                    @forelse ($newHires as $hire)
                        <li>
                            <a href="{{ EmployeeResource::getUrl('view', ['record' => $hire]) }}" class="hd-list-item">
                                <span class="hd-avatar" style="--c:#10b981">{{ $hire->initials() }}</span>
                                <span class="hd-list-main">
                                    <b>{{ $hire->name }}</b>
                                    <small>{{ $hire->job_title }} · starts {{ $hire->start_date->format('M j') }}</small>
                                    <span class="hd-progress"><span style="width: {{ $hire->progress() }}%"></span></span>
                                </span>
                                <span class="hd-pct">{{ $hire->progress() }}%</span>
                            </a>
                        </li>
                    @empty
                        <li class="hd-empty">No one is onboarding right now.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>
</x-filament-panels::page>
