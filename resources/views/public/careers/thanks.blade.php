@extends('public.layout')

@section('title', 'Thank you')

@section('content')
    <section class="s-wrap t-wrap">
        <div class="t-card">
            <div class="t-check">✓</div>
            <h1>Thank you{{ $name ? ', '.$name : '' }}!</h1>
            @if ($jobTitle)
                <p>Your application for <b>{{ $jobTitle }}</b> has been received. We've emailed you a confirmation and will be in touch within a few days.</p>
            @else
                <p>You're in our talent pool. We'll reach out when a role matches your interests.</p>
            @endif
            <div class="t-actions">
                <a href="{{ route('careers.index') }}" class="s-btn s-btn-brand">See more jobs</a>
                <a href="{{ route('careers.talent-pool') }}" class="s-btn s-btn-outline">Get job alerts</a>
            </div>
        </div>
    </section>
@endsection
