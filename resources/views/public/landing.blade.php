@extends('public.layout')

@section('title', 'HireDesk — Applicant tracking & onboarding')
@section('description', 'HireDesk: hire faster with job posting, applicant tracking, custom pipeline stages, interview scheduling, e-signature and paperless onboarding.')
@section('body_class', 'is-landing')

@section('nav')
    <header class="s-nav s-nav-dark">
        <div class="s-wrap s-nav-inner">
            <a href="{{ route('home') }}" class="s-logo">
                <span class="s-logo-mark s-logo-indigo">H</span><span>Hire<b>Desk</b></span>
            </a>
            <nav class="s-nav-links">
                <a href="#features" class="s-hide-sm">Features</a>
                <a href="#demo" class="s-hide-sm">Demo</a>
                <a href="{{ url('/admin') }}" class="s-btn s-btn-white s-btn-sm">Sign in</a>
            </nav>
        </div>
    </header>
@endsection

@section('content')
    <section class="l-hero">
        <div class="l-hero-bg"></div>
        <div class="s-wrap l-hero-inner">
            <div class="l-hero-copy">
                <span class="l-badge">Recruit · Onboard · Report</span>
                <h1>Hire great people <span>without the paperwork.</span></h1>
                <p>Post jobs everywhere, screen applicants automatically, move candidates through your own pipeline stages, and onboard new hires before day one — all in one simple system.</p>
                <div class="l-cta">
                    <a href="{{ url('/admin') }}" class="s-btn s-btn-indigo s-btn-lg">Open the live demo →</a>
                    <a href="{{ route('careers.index') }}" class="s-btn s-btn-glass s-btn-lg">View careers site</a>
                </div>
                <p class="l-note">Demo login is pre-filled. Sample data resets automatically.</p>
            </div>
            <div class="l-hero-visual" aria-hidden="true">
                <div class="l-window">
                    <div class="l-window-bar"><i></i><i></i><i></i><span>Hiring pipeline · Registered Nurse – ICU</span></div>
                    <div class="l-kanban">
                        @foreach ([['New', '#64748b', ['Ethan Brooks · 88', 'Sofia Nguyen · 74', 'Liam Patel · 41']], ['Screening', '#0ea5e9', ['James Carter · 92', 'Olivia Reed · 81']], ['Interview', '#6366f1', ['Priya Shah · 86', 'Lucas Moore · 79']], ['Offer', '#f59e0b', ['Maria Lopez · 95']], ['Hired', '#10b981', ['Aisha Bello · 91']]] as [$name, $color, $cards])
                            <div class="l-col" style="--c: {{ $color }}">
                                <b><i></i>{{ $name }}</b>
                                @foreach ($cards as $card)<span>{{ $card }}</span>@endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="l-float l-float-1">⚡ Offer letter sent for e-signature</div>
                <div class="l-float l-float-2">✅ Aisha completed 6 of 10 onboarding tasks</div>
            </div>
        </div>
    </section>

    <section class="l-logos">
        <div class="s-wrap"><span>Built for growing teams in</span><b>Healthcare</b><b>Home care</b><b>Hospitality</b><b>Retail</b><b>Non-profits</b></div>
    </section>

    <section id="features" class="l-section">
        <div class="s-wrap">
            <div class="l-head"><h2>Everything you need to hire and onboard</h2><p>One modern system for recruiters, hiring managers and new hires.</p></div>
            <div class="l-features">
                @foreach ([
                    ['🧭', 'Custom pipeline stages', 'Build a workflow per job type. Colour-code stages, set SLAs and attach automations that fire when candidates enter.'],
                    ['📣', 'Post everywhere', 'Branded careers site, mobile apply, XML feed for Indeed and ZipRecruiter, and share links for social recruiting.'],
                    ['🎯', 'Smart screening', 'Questionnaires with knock-out questions, weighted scoring and resume keyword points rank applicants for you.'],
                    ['📄', 'Resume parsing & search', 'Skills extracted automatically. Boolean search and ZIP-code radius search across your talent pool.'],
                    ['📅', 'Interviews & scorecards', 'Schedule interviews with calendar invites, collect structured feedback and compare ratings.'],
                    ['💬', 'Email & texting', 'Branded templates, two-way messages and automatic updates on every stage change.'],
                    ['✍️', 'Offers with e-signature', 'Approval workflow, then candidates review and sign on any device.'],
                    ['🚀', 'Paperless onboarding', 'New-hire portal with forms, e-signed policies, uploads and HR/IT checklists before day one.'],
                    ['📊', 'Reports that matter', 'Time to hire, source effectiveness, funnel conversion, EEO summaries and CSV export.'],
                ] as [$icon, $title, $text])
                    <article class="l-feature"><span>{{ $icon }}</span><h3>{{ $title }}</h3><p>{{ $text }}</p></article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="demo" class="l-section l-demo">
        <div class="s-wrap">
            <div class="l-head"><h2>Try the demo</h2><p>Three ways to explore — each opens the live app with realistic sample data.</p></div>
            <div class="l-demo-grid">
                <a href="{{ url('/admin') }}" class="l-demo-card">
                    <b>Recruiter dashboard</b><p>Pipeline board, candidate profiles, stages editor, reports.</p><span>Open admin →</span>
                </a>
                <a href="{{ route('careers.index') }}" class="l-demo-card">
                    <b>Careers site</b><p>Search jobs by keyword and distance, then apply with a resume and screening questions.</p><span>Browse jobs →</span>
                </a>
                @php $portal = \App\Models\Employee::where('status', 'onboarding')->orderBy('start_date')->first(); @endphp
                @if ($portal)
                    <a href="{{ $portal->portalUrl() }}" class="l-demo-card">
                        <b>New-hire portal</b><p>See onboarding through {{ $portal->first_name }}'s eyes: forms, e-signatures and uploads.</p><span>Open portal →</span>
                    </a>
                @endif
            </div>
        </div>
    </section>
@endsection
