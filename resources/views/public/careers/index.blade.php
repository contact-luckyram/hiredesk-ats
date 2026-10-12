@extends('public.layout')

@section('content')
    <section class="c-hero">
        <div class="s-wrap">
            <h1>{{ config('hiredesk.company_tagline') }}</h1>
            <p>{{ $total }} open {{ \Illuminate\Support\Str::plural('role', $total) }} across Massachusetts — nursing, home care, therapy and operations.</p>
            <form method="GET" action="{{ route('careers.index') }}" class="c-search">
                <label><span class="sr-only">Keyword</span><input type="search" name="q" value="{{ request('q') }}" placeholder="Job title or keyword"></label>
                <label><span class="sr-only">ZIP code</span><input type="text" name="zip" value="{{ request('zip') }}" placeholder="ZIP code" inputmode="numeric" maxlength="10"></label>
                <label><span class="sr-only">Distance</span>
                    <select name="miles">
                        @foreach ([10, 25, 50, 100] as $m)<option value="{{ $m }}" @selected((int) request('miles', 25) === $m)>Within {{ $m }} miles</option>@endforeach
                    </select>
                </label>
                <button class="s-btn s-btn-brand">Search jobs</button>
            </form>
        </div>
    </section>

    <section class="s-wrap c-layout">
        <aside class="c-filters">
            <form method="GET" action="{{ route('careers.index') }}" id="filters">
                <input type="hidden" name="q" value="{{ request('q') }}">
                <input type="hidden" name="zip" value="{{ request('zip') }}">
                <input type="hidden" name="miles" value="{{ request('miles') }}">
                <details open>
                    <summary>Department</summary>
                    @foreach ($departments as $d)
                        <label class="c-check"><input type="checkbox" name="department[]" value="{{ $d->id }}" @checked(in_array($d->id, (array) request('department'))) onchange="this.form.submit()"> {{ $d->name }} <small>({{ $d->job_openings_count }})</small></label>
                    @endforeach
                </details>
                <details open>
                    <summary>Job type</summary>
                    @foreach ($types as $t)
                        <label class="c-check"><input type="checkbox" name="type[]" value="{{ $t->value }}" @checked(in_array($t->value, (array) request('type'))) onchange="this.form.submit()"> {{ $t->getLabel() }}</label>
                    @endforeach
                </details>
                <details open>
                    <summary>Workplace</summary>
                    @foreach ($workplaces as $w)
                        <label class="c-check"><input type="checkbox" name="workplace[]" value="{{ $w->value }}" @checked(in_array($w->value, (array) request('workplace'))) onchange="this.form.submit()"> {{ $w->getLabel() }}</label>
                    @endforeach
                </details>
                <noscript><button class="s-btn s-btn-brand">Apply filters</button></noscript>
                @if (request()->hasAny(['q', 'zip', 'department', 'type', 'workplace']))
                    <a href="{{ route('careers.index') }}" class="c-clear">Clear all filters</a>
                @endif
            </form>
        </aside>

        <div class="c-results">
            <p class="c-count">
                Showing <b>{{ $jobs->count() }}</b> {{ \Illuminate\Support\Str::plural('job', $jobs->count()) }}
                @if ($origin) within {{ request('miles', 25) }} miles of {{ $origin->city }} ({{ $origin->zip }}) @endif
            </p>
            @if ($zipNotFound)
                <p class="c-alert">We couldn't find ZIP code “{{ request('zip') }}”, so distance isn't applied.</p>
            @endif

            @forelse ($jobs as $job)
                <a href="{{ route('careers.show', $job) }}" class="c-job">
                    <div>
                        <h3>{{ $job->title }} @if($job->is_featured)<span class="c-hot">Hiring now</span>@endif</h3>
                        <p class="c-meta">
                            <span>📍 {{ $job->locationLabel() }}@isset($job->distance) · {{ $job->distance }} mi @endisset</span>
                            <span>{{ $job->employment_type->getLabel() }}</span>
                            @if ($job->salaryLabel())<span>{{ $job->salaryLabel() }}</span>@endif
                            <span>{{ $job->department?->name }}</span>
                        </p>
                        @if ($job->summary)<p class="c-summary">{{ \Illuminate\Support\Str::limit($job->summary, 150) }}</p>@endif
                    </div>
                    <span class="s-btn s-btn-outline">View job</span>
                </a>
            @empty
                <div class="c-empty">
                    <h3>No jobs match your search</h3>
                    <p>Try a different keyword or a wider distance — or join our talent pool and we'll reach out when something fits.</p>
                    <a href="{{ route('careers.talent-pool') }}" class="s-btn s-btn-brand">Join the talent pool</a>
                </div>
            @endforelse
        </div>
    </section>

    <section id="benefits" class="c-benefits">
        <div class="s-wrap">
            <h2>Why work with us</h2>
            <div class="c-benefit-grid">
                @foreach ([['🩺', 'Health from day one', 'Medical, dental and vision coverage starting your first day.'], ['💰', '401(k) with 5% match', 'Plan for the future with a generous company match.'], ['🎓', 'Tuition support', 'Up to $5,250 a year toward degrees and certifications.'], ['🌴', 'Paid time off', 'PTO that grows with you, plus paid holidays.']] as [$i, $t, $d])
                    <div class="c-benefit"><span>{{ $i }}</span><b>{{ $t }}</b><p>{{ $d }}</p></div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
