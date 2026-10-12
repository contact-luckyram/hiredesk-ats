<x-filament-panels::page>
    <div class="hd-toolbar">
        <label class="hd-field">
            <span>Period</span>
            <select wire:model.live="range" class="hd-select">
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
                <option value="180">Last 6 months</option>
                <option value="365">Last 12 months</option>
            </select>
        </label>
        <div class="hd-toolbar-stat">Reports update live as candidates move through your pipelines.</div>
    </div>

    <section class="hd-kpis hd-kpis-6">
        <div class="hd-card hd-kpi"><div class="hd-kpi-top">Applications</div><div class="hd-kpi-row"><strong>{{ number_format($kpis['applications']) }}</strong></div></div>
        <div class="hd-card hd-kpi"><div class="hd-kpi-top">Hires</div><div class="hd-kpi-row"><strong>{{ $kpis['hires'] }}</strong></div></div>
        <div class="hd-card hd-kpi"><div class="hd-kpi-top">Avg. time to hire</div><div class="hd-kpi-row"><strong>{{ $kpis['time_to_hire'] ?? '—' }}<small> days</small></strong></div></div>
        <div class="hd-card hd-kpi"><div class="hd-kpi-top">Offer acceptance</div><div class="hd-kpi-row"><strong>{{ $kpis['acceptance'] !== null ? $kpis['acceptance'].'%' : '—' }}</strong></div></div>
        <div class="hd-card hd-kpi"><div class="hd-kpi-top">Applicants per hire</div><div class="hd-kpi-row"><strong>{{ $kpis['per_hire'] ?? '—' }}</strong></div></div>
        <div class="hd-card hd-kpi"><div class="hd-kpi-top">Paperwork done before day 1</div><div class="hd-kpi-row"><strong>{{ $kpis['onboarding'] !== null ? $kpis['onboarding'].'%' : '—' }}</strong></div></div>
    </section>

    <section class="hd-grid hd-grid-2">
        <div class="hd-card">
            <div class="hd-card-head"><div><h3>Hiring funnel</h3><p>Share of applicants reaching each step</p></div></div>
            @php $top = max($funnel[0][1], 1); @endphp
            <div class="hd-bigfunnel">
                @foreach ($funnel as $i => [$label, $count, $color])
                    <div class="hd-bigfunnel-row">
                        <span class="hd-bigfunnel-label">{{ $label }}</span>
                        <div class="hd-bigfunnel-track"><span style="width: {{ max(2, $count / $top * 100) }}%; background: {{ $color }}"></span></div>
                        <span class="hd-bigfunnel-num"><b>{{ number_format($count) }}</b><small>{{ round($count / $top * 100, 1) }}%</small></span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="hd-card">
            <div class="hd-card-head"><div><h3>Applications &amp; hires by month</h3><p>Last 12 months</p></div>
                <div class="hd-legend"><span><i style="background:#c7d2fe"></i>Applications</span><span><i style="background:#10b981"></i>Hires</span></div></div>
            <div class="hd-bars">
                @foreach ($monthly as $m)
                    <div class="hd-bars-col" title="{{ $m['label'] }}: {{ $m['applications'] }} applications, {{ $m['hires'] }} hires">
                        <div class="hd-bars-stack">
                            <span class="hd-bars-a" style="height: {{ $m['applications'] / $monthlyMax * 100 }}%"><em>{{ $m['applications'] ?: '' }}</em></span>
                            <span class="hd-bars-h" style="height: {{ $m['hires'] / $monthlyMax * 100 }}%"></span>
                        </div>
                        <small>{{ $m['label'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="hd-grid hd-grid-2">
        <div class="hd-card hd-card-flush">
            <div class="hd-card-head hd-pad"><div><h3>Source effectiveness</h3><p>Which channels produce interviews and hires</p></div></div>
            <div class="hd-table-wrap">
                <table class="hd-table">
                    <thead><tr><th>Source</th><th class="hd-num">Applications</th><th class="hd-num">Interviewed</th><th class="hd-num">Hires</th><th class="hd-num">Hire rate</th></tr></thead>
                    <tbody>
                    @foreach ($sources as $row)
                        <tr>
                            <td><b>{{ $row['source'] }}</b></td>
                            <td class="hd-num">{{ $row['applications'] }}</td>
                            <td class="hd-num">{{ $row['interviews'] }}</td>
                            <td class="hd-num">{{ $row['hires'] }}</td>
                            <td class="hd-num"><span class="hd-pill {{ $row['applications'] && $row['hires'] / $row['applications'] >= 0.1 ? 'hd-pill-emerald' : 'hd-pill-slate' }}">{{ $row['applications'] ? round($row['hires'] / $row['applications'] * 100, 1) : 0 }}%</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="hd-card hd-card-flush">
            <div class="hd-card-head hd-pad"><div><h3>Time to hire by department</h3><p>Average days from application to hire</p></div></div>
            @php $maxDays = max(collect($byDept)->max('days') ?? 1, 1); @endphp
            <div class="hd-pad hd-hbars">
                @foreach ($byDept as $d)
                    <div class="hd-hbar">
                        <span class="hd-hbar-label">{{ $d['name'] }}</span>
                        <div class="hd-hbar-track"><span style="width: {{ $d['days'] ? $d['days'] / $maxDays * 100 : 0 }}%; background: {{ $d['color'] }}"></span></div>
                        <span class="hd-hbar-num">{{ $d['days'] !== null ? $d['days'].' d' : '—' }} <small>{{ $d['hires'] }} hires</small></span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="hd-grid hd-grid-2-1">
        <div class="hd-card hd-card-flush">
            <div class="hd-card-head hd-pad"><div><h3>EEO / voluntary self-identification</h3><p>{{ $eeoTotal }} applicants answered · aggregated, never shown on individual profiles</p></div></div>
            <div class="hd-eeo">
                @foreach ($eeo as $group)
                    <div class="hd-eeo-group">
                        <h4>{{ $group['label'] }}</h4>
                        <table class="hd-table hd-table-compact">
                            <thead><tr><th></th><th class="hd-num">Applicants</th><th class="hd-num">Hires</th></tr></thead>
                            <tbody>
                            @foreach ($group['rows'] as $row)
                                <tr><td>{{ $row['value'] }}</td><td class="hd-num">{{ $row['applicants'] }} <small class="hd-muted">({{ $group['total'] ? round($row['applicants'] / $group['total'] * 100) : 0 }}%)</small></td><td class="hd-num">{{ $row['hires'] }}</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="hd-card hd-card-flush">
            <div class="hd-card-head hd-pad"><div><h3>Interviewer activity</h3><p>Interviews and scorecards submitted</p></div></div>
            <div class="hd-table-wrap">
                <table class="hd-table">
                    <thead><tr><th>Team member</th><th class="hd-num">Interviews</th><th class="hd-num">Scorecards</th><th class="hd-num">Avg. rating</th></tr></thead>
                    <tbody>
                    @forelse ($interviewers as $row)
                        <tr><td><b>{{ $row['name'] }}</b></td><td class="hd-num">{{ $row['interviews'] }}</td><td class="hd-num">{{ $row['scorecards'] }}</td><td class="hd-num">{{ $row['avg'] ?: '—' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="hd-empty">No interviews in this period.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</x-filament-panels::page>
