@extends('public.layout')

@section('title', $job->title.' · '.config('hiredesk.company'))
@section('description', $job->summary)
@section('og')
    <meta property="og:title" content="{{ $job->title }} · {{ config('hiredesk.company') }}">
    <meta property="og:description" content="{{ $job->summary }}">
    <meta property="og:url" content="{{ route('careers.show', $job) }}">
    <meta property="og:type" content="website">
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $job->title, 'description' => $job->description,
        'datePosted' => $job->published_at?->toDateString(), 'validThrough' => $job->closes_at?->toDateString(),
        'employmentType' => strtoupper($job->employment_type->value),
        'hiringOrganization' => ['@type' => 'Organization', 'name' => config('hiredesk.company')],
        'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $job->location?->city, 'addressRegion' => $job->location?->state, 'postalCode' => $job->location?->zip, 'addressCountry' => 'US']],
    ], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endsection

@section('content')
    <section class="j-head">
        <div class="s-wrap">
            <a href="{{ route('careers.index') }}" class="j-back">← All jobs</a>
            <h1>{{ $job->title }}</h1>
            <p class="c-meta">
                <span>📍 {{ $job->locationLabel() }}</span>
                <span>{{ $job->employment_type->getLabel() }}</span>
                <span>{{ $job->workplace->getLabel() }}</span>
                @if ($job->salaryLabel())<span>{{ $job->salaryLabel() }}</span>@endif
                <span>{{ $job->department?->name }}</span>
            </p>
            <div class="j-actions">
                <a href="{{ route('careers.apply', $job) }}{{ request('source') ? '?source='.urlencode(request('source')) : '' }}" class="s-btn s-btn-brand s-btn-lg">Apply now</a>
                <span class="j-share">Share:
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('careers.show', $job).'?source=linkedin') }}" target="_blank" rel="noopener">LinkedIn</a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('careers.show', $job).'?source=facebook') }}" target="_blank" rel="noopener">Facebook</a>
                    <a href="https://twitter.com/intent/tweet?text={{ urlencode('We\'re hiring: '.$job->title) }}&url={{ urlencode(route('careers.show', $job).'?source=x') }}" target="_blank" rel="noopener">X</a>
                    <a href="mailto:?subject={{ rawurlencode('Job: '.$job->title) }}&body={{ rawurlencode(route('careers.show', $job).'?source=referral') }}">Email a friend</a>
                </span>
            </div>
        </div>
    </section>

    <section class="s-wrap j-body">
        <article class="j-desc">
            @if ($job->summary)<p class="j-lead">{{ $job->summary }}</p>@endif
            <div class="s-prose">{!! $job->description !!}</div>
        </article>
        <aside class="j-side">
            <div class="j-card">
                <h3>At a glance</h3>
                <dl>
                    <dt>Location</dt><dd>{{ $job->location?->name ?? 'Remote' }}</dd>
                    <dt>Schedule</dt><dd>{{ $job->employment_type->getLabel() }}</dd>
                    @if ($job->salaryLabel())<dt>Pay</dt><dd>{{ $job->salaryLabel() }}</dd>@endif
                    <dt>Openings</dt><dd>{{ $job->openings }}</dd>
                    @if ($job->closes_at)<dt>Apply by</dt><dd>{{ $job->closes_at->format('M j, Y') }}</dd>@endif
                    <dt>Reference</dt><dd>{{ $job->reference }}</dd>
                </dl>
                <a href="{{ route('careers.apply', $job) }}" class="s-btn s-btn-brand s-btn-block">Apply — takes 3 minutes</a>
            </div>
            @if ($related->count())
                <div class="j-card">
                    <h3>Similar roles</h3>
                    @foreach ($related as $r)
                        <a href="{{ route('careers.show', $r) }}" class="j-related"><b>{{ $r->title }}</b><small>{{ $r->locationLabel() }}</small></a>
                    @endforeach
                </div>
            @endif
        </aside>
    </section>
@endsection
