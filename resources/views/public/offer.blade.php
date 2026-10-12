@extends('public.layout')

@section('title', 'Offer letter · '.config('hiredesk.company'))
@section('body_class', $print ? 'is-print' : '')

@section('content')
    @php $c = $offer->application->candidate; $job = $offer->application->jobOpening; $signable = in_array($offer->status->value, ['sent', 'approved']); @endphp
    <section class="s-wrap o-wrap">
        @if (session('status') === 'signed')
            <div class="c-alert c-alert-success">🎉 Congratulations, {{ $c->first_name }}! Your signed offer has been sent to our HR team. Watch your inbox for your new-hire portal link.</div>
        @elseif (session('status') === 'declined')
            <div class="c-alert">You declined this offer. Thank you for letting us know — we wish you the very best.</div>
        @endif

        <article class="o-letter">
            <header class="o-letterhead">
                <span class="s-logo"><span class="s-logo-mark">{{ mb_substr(config('hiredesk.company'), 0, 1) }}</span><span>{{ config('hiredesk.company') }}</span></span>
                <span class="o-date">{{ ($offer->sent_at ?? $offer->created_at)->format('F j, Y') }}</span>
            </header>
            <h1>Offer of employment</h1>
            <dl class="o-terms">
                <div><dt>Position</dt><dd>{{ $job->title }}</dd></div>
                <div><dt>Compensation</dt><dd>{{ $offer->salaryLabel() }}</dd></div>
                <div><dt>Start date</dt><dd>{{ $offer->start_date->format('F j, Y') }}</dd></div>
                <div><dt>Location</dt><dd>{{ $job->locationLabel() }}</dd></div>
            </dl>
            <div class="o-body">{!! nl2br(e($offer->body)) !!}</div>

            @if ($offer->status->value === 'signed')
                <div class="o-signed">
                    @if ($offer->signature)<img src="{{ $offer->signature }}" alt="Signature of {{ $offer->signed_name }}">@endif
                    <p><b>{{ $offer->signed_name }}</b> · Accepted electronically on {{ $offer->signed_at->format('F j, Y \a\t g:i A') }}</p>
                </div>
            @endif
        </article>

        @if ($signable && ! $print)
            <form method="POST" action="{{ route('offers.sign', $offer->token) }}" class="o-sign a-card">
                @csrf
                <h2>Accept &amp; sign</h2>
                @if ($offer->expires_at)<p class="a-help">Please respond by {{ $offer->expires_at->format('F j, Y') }}.</p>@endif
                <label class="a-field @error('signed_name') has-error @enderror">
                    <span>Type your full legal name<em>*</em></span>
                    <input type="text" name="signed_name" value="{{ old('signed_name', $c->name) }}" required>
                    @error('signed_name')<small>{{ $message }}</small>@enderror
                </label>
                <div class="a-field @error('signature') has-error @enderror">
                    <span>Draw your signature<em>*</em></span>
                    <canvas class="sig-pad" data-signature="offer-signature" aria-label="Signature pad"></canvas>
                    <input type="hidden" name="signature" id="offer-signature">
                    <button type="button" class="sig-clear" data-clear="offer-signature">Clear</button>
                    @error('signature')<small>{{ $message }}</small>@enderror
                </div>
                <label class="c-check @error('agree') has-error @enderror"><input type="checkbox" name="agree" value="1"> I accept this offer and agree that my electronic signature is legally binding.</label>
                @error('agree')<small class="a-error">{{ $message }}</small>@enderror
                <div class="o-actions">
                    <button class="s-btn s-btn-brand s-btn-lg">Accept offer</button>
                </div>
            </form>
            <form method="POST" action="{{ route('offers.decline', $offer->token) }}" class="o-decline" onsubmit="return confirm('Decline this offer?')">
                @csrf
                <button class="s-link-btn">I'd like to decline this offer</button>
            </form>
        @elseif (! $signable && $offer->status->value !== 'signed' && $offer->status->value !== 'declined')
            <p class="c-alert">This offer is still being prepared. You'll receive an email when it's ready to sign.</p>
        @endif

        @if ($print)
            <script>window.addEventListener('load', () => window.print());</script>
        @endif
    </section>
@endsection
