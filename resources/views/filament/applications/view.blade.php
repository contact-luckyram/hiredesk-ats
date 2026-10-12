@php
    use App\Filament\Resources\Employees\EmployeeResource;
    use App\Filament\Resources\Offers\OfferResource;
    use App\Filament\Resources\Applications\ApplicationResource;
    use App\Enums\StageCategory;
    $c = $app->candidate;
    $breakdown = $app->answers['breakdown'] ?? [];
    $currentIndex = $stages->search(fn ($s) => $s->id === $app->stage_id);
@endphp

<x-filament-panels::page>
    {{-- Header card --}}
    <section class="hd-card hd-profile-head">
        <div class="hd-profile-id">
            <span class="hd-avatar hd-avatar-lg" style="--c: {{ $app->stage?->color ?? '#6366f1' }}">{{ $c->initials() }}</span>
            <div>
                <div class="hd-profile-name">
                    {{ $c->name }}
                    @if ($app->stage)<span class="hd-chip" style="--c: {{ $app->stage->color }}">{{ $app->stage->name }}</span>@endif
                    @if ($app->knocked_out)<span class="hd-pill hd-pill-rose">Knock-out</span>@endif
                    @if ($c->in_talent_pool)<span class="hd-pill hd-pill-emerald">Talent pool</span>@endif
                </div>
                <p class="hd-muted">
                    {{ $app->jobOpening->title }} · {{ $c->current_title ?? 'Candidate' }}{{ $c->locationLabel() ? ' · '.$c->locationLabel() : '' }}
                </p>
                <div class="hd-profile-contacts">
                    <a href="mailto:{{ $c->email }}"><x-filament::icon icon="heroicon-m-envelope" class="hd-i"/>{{ $c->email }}</a>
                    @if ($c->phone)<a href="tel:{{ $c->phone }}"><x-filament::icon icon="heroicon-m-phone" class="hd-i"/>{{ $c->phone }}</a>@endif
                    @if ($c->linkedin_url)<a href="{{ $c->linkedin_url }}" target="_blank" rel="noopener"><x-filament::icon icon="heroicon-m-link" class="hd-i"/>LinkedIn</a>@endif
                </div>
            </div>
        </div>
        <div class="hd-profile-metrics">
            <div><span>Screening</span><strong class="hd-text-{{ $app->scoreColor() }}">{{ $app->score ?? '—' }}<small>/100</small></strong></div>
            <div><span>Team rating</span><strong>{{ $app->averageScorecard() ?? '—' }}<small>/5</small></strong></div>
            <div>
                <span>Your rating</span>
                <span class="hd-stars hd-stars-lg">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" wire:click="setRating({{ $i }})" @class(['on' => $i <= ($app->rating ?? 0)]) aria-label="Rate {{ $i }}">★</button>
                    @endfor
                </span>
            </div>
            <div><span>Applied</span><strong class="hd-strong-sm">{{ $app->applied_at?->format('M j, Y') }}</strong><small class="hd-muted">{{ $app->source }}</small></div>
        </div>
    </section>

    {{-- Stage bar --}}
    <nav class="hd-stagebar" aria-label="Pipeline stages">
        @foreach ($stages as $i => $stage)
            @continue($stage->category === StageCategory::Rejected && $app->stage_id !== $stage->id)
            <button
                type="button"
                wire:click="moveTo({{ $stage->id }})"
                @disabled($stage->id === $app->stage_id)
                @class([
                    'hd-stagebar-step',
                    'is-current' => $stage->id === $app->stage_id,
                    'is-done' => $currentIndex !== false && $i < $currentIndex && $app->stage?->category !== StageCategory::Rejected,
                    'is-rejected' => $stage->category === StageCategory::Rejected,
                ])
                style="--c: {{ $stage->color }}"
                title="Move to {{ $stage->name }}"
            >{{ $stage->name }}</button>
        @endforeach
    </nav>

    <div class="hd-grid hd-grid-2-1">
        <div class="hd-stack">
            <section class="hd-card" x-data="{ tab: 'overview' }">
                <div class="hd-tabs" role="tablist">
                    @foreach (['overview' => 'Overview', 'questionnaire' => 'Questionnaire', 'resume' => 'Resume', 'scorecards' => 'Scorecards ('.$app->scorecards->count().')', 'messages' => 'Messages ('.$app->messages->count().')', 'interviews' => 'Interviews ('.$app->interviews->count().')'] as $key => $label)
                        <button type="button" role="tab" x-on:click="tab = '{{ $key }}'" x-bind:class="{ 'is-active': tab === '{{ $key }}' }">{{ $label }}</button>
                    @endforeach
                </div>

                <div x-show="tab === 'overview'">
                    <div class="hd-grid hd-grid-2 hd-gap-lg">
                        <div>
                            <h4 class="hd-h4">Profile</h4>
                            <dl class="hd-dl">
                                <dt>Experience</dt><dd>{{ $c->years_experience !== null ? $c->years_experience.' years' : '—' }}</dd>
                                <dt>Current title</dt><dd>{{ $c->current_title ?? '—' }}</dd>
                                <dt>Location</dt><dd>{{ $c->locationLabel() ?? '—' }}{{ $c->zip ? ' '.$c->zip : '' }}</dd>
                                <dt>Source</dt><dd>{{ $app->source ?? $c->source ?? '—' }}</dd>
                                <dt>In stage</dt><dd>{{ $app->daysInStage() }} days @if($app->isOverSla())<span class="hd-pill hd-pill-amber">over {{ $app->stage->sla_days }}d SLA</span>@endif</dd>
                                <dt>Texting</dt><dd>{{ $c->sms_opt_in ? 'Opted in' : 'Opted out' }}</dd>
                            </dl>
                        </div>
                        <div>
                            <h4 class="hd-h4">Skills</h4>
                            <div class="hd-tags">
                                @forelse ($c->skills ?? [] as $skill)<span class="hd-tag">{{ $skill }}</span>@empty<span class="hd-muted">No skills detected yet.</span>@endforelse
                            </div>
                            @if (count($breakdown))
                                <h4 class="hd-h4 hd-mt">Screening breakdown</h4>
                                <div class="hd-breakdown">
                                    @foreach ($breakdown as $row)
                                        <div class="hd-breakdown-row">
                                            <span title="{{ $row['label'] }}">{{ \Illuminate\Support\Str::limit($row['label'], 38) }}</span>
                                            @if ($row['knockout'])
                                                <span class="hd-pill {{ $row['passed'] ? 'hd-pill-emerald' : 'hd-pill-rose' }}">{{ $row['passed'] ? 'Pass' : 'Fail' }}</span>
                                            @else
                                                <span class="hd-mini-bar"><i style="width: {{ $row['max'] ? $row['points'] / $row['max'] * 100 : 0 }}%"></i></span>
                                                <b>{{ $row['points'] }}/{{ $row['max'] }}</b>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                    @if ($app->cover_letter)
                        <h4 class="hd-h4 hd-mt">Cover letter</h4>
                        <p class="hd-prose">{!! nl2br(e($app->cover_letter)) !!}</p>
                    @endif
                    @if ($otherApplications->count())
                        <h4 class="hd-h4 hd-mt">Other applications</h4>
                        <ul class="hd-list">
                            @foreach ($otherApplications as $other)
                                <li><a class="hd-list-item" href="{{ ApplicationResource::getUrl('view', ['record' => $other]) }}"><span class="hd-list-main"><b>{{ $other->jobOpening->title }}</b><small>Applied {{ $other->applied_at?->format('M j, Y') }}</small></span><span class="hd-chip" style="--c: {{ $other->stage?->color ?? '#64748b' }}">{{ $other->stage?->name }}</span></a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div x-show="tab === 'questionnaire'" x-cloak>
                    @if ($app->jobOpening->questionnaire && count($breakdown))
                        <table class="hd-table">
                            <thead><tr><th>Question</th><th>Answer</th><th class="hd-num">Points</th></tr></thead>
                            <tbody>
                            @foreach ($breakdown as $row)
                                <tr>
                                    <td>{{ $row['label'] }} @if($row['knockout'])<span class="hd-pill hd-pill-slate">Knock-out</span>@endif</td>
                                    <td>{{ $row['answer'] ?? '' ?: '—' }}</td>
                                    <td class="hd-num"><span class="hd-pill {{ $row['passed'] ? 'hd-pill-emerald' : 'hd-pill-rose' }}">{{ $row['points'] }}/{{ $row['max'] }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="hd-empty">This job has no screening questionnaire.</p>
                    @endif
                </div>

                <div x-show="tab === 'resume'" x-cloak>
                    @if ($c->resume_path)
                        <p><a class="hd-link" href="{{ route('resumes.download', $c) }}">Download original resume</a></p>
                    @endif
                    <pre class="hd-resume">{{ $c->resume_text ?: 'No resume text on file.' }}</pre>
                </div>

                <div x-show="tab === 'scorecards'" x-cloak>
                    @forelse ($app->scorecards as $card)
                        <article class="hd-scorecard">
                            <header>
                                <b>{{ $card->user?->name ?? 'Team member' }}</b>
                                <span class="hd-muted">{{ $card->round }} · {{ $card->created_at->format('M j') }}</span>
                                <span class="hd-stars">@for ($i = 1; $i <= 5; $i++)<i @class(['on' => $i <= $card->rating])>★</i>@endfor</span>
                                <x-filament::badge :color="$card->recommendation->getColor()">{{ $card->recommendation->getLabel() }}</x-filament::badge>
                            </header>
                            @if ($card->strengths)<p><b>Strengths:</b> {{ $card->strengths }}</p>@endif
                            @if ($card->concerns)<p><b>Concerns:</b> {{ $card->concerns }}</p>@endif
                        </article>
                    @empty
                        <p class="hd-empty">No scorecards yet. Use “More → Add scorecard”.</p>
                    @endforelse
                </div>

                <div x-show="tab === 'messages'" x-cloak>
                    <div class="hd-thread">
                        @forelse ($app->messages->sortBy('sent_at') as $message)
                            <div @class(['hd-bubble', 'is-in' => $message->direction === 'inbound', 'is-sms' => $message->channel->value === 'sms'])>
                                <div class="hd-bubble-meta">{{ $message->channel->getLabel() }} · {{ $message->direction === 'inbound' ? 'from candidate' : ($message->automated ? 'automated' : 'sent') }} · {{ $message->sent_at?->format('M j, g:i A') }}</div>
                                @if ($message->subject)<b>{{ $message->subject }}</b>@endif
                                <p>{!! nl2br(e($message->body)) !!}</p>
                            </div>
                        @empty
                            <p class="hd-empty">No messages yet.</p>
                        @endforelse
                    </div>
                </div>

                <div x-show="tab === 'interviews'" x-cloak>
                    <ul class="hd-list">
                        @forelse ($app->interviews as $interview)
                            <li class="hd-list-item">
                                <span class="hd-date-badge @if($interview->scheduled_at->isToday()) is-today @endif"><small>{{ $interview->scheduled_at->format('M') }}</small>{{ $interview->scheduled_at->format('j') }}</span>
                                <span class="hd-list-main"><b>{{ $interview->title }}</b><small>{{ $interview->type->getLabel() }} · {{ $interview->scheduled_at->format('g:i A') }} · {{ $interview->duration_minutes }} min · {{ $interview->interviewers()->pluck('name')->implode(', ') }}</small></span>
                                <x-filament::badge :color="$interview->status->getColor()">{{ $interview->status->getLabel() }}</x-filament::badge>
                                <a class="hd-link" href="{{ route('interviews.ics', $interview) }}">.ics</a>
                            </li>
                        @empty
                            <li class="hd-empty">No interviews scheduled.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        </div>

        <div class="hd-stack">
            @if ($app->offer)
                <section class="hd-card hd-offer-card">
                    <div class="hd-card-head"><div><h3>Offer</h3><p>{{ $app->offer->salaryLabel() }} · starts {{ $app->offer->start_date->format('M j') }}</p></div>
                        <x-filament::badge :color="$app->offer->status->getColor()">{{ $app->offer->status->getLabel() }}</x-filament::badge></div>
                    <div class="hd-row-gap">
                        <a class="hd-btn hd-btn-soft" href="{{ OfferResource::getUrl('edit', ['record' => $app->offer]) }}">Manage offer</a>
                        @if (in_array($app->offer->status->value, ['sent', 'signed']))<a class="hd-btn hd-btn-soft" href="{{ $app->offer->signingUrl() }}" target="_blank">Signing page</a>@endif
                    </div>
                </section>
            @endif

            @if ($app->employee)
                <section class="hd-card">
                    <div class="hd-card-head"><div><h3>Onboarding</h3><p>Starts {{ $app->employee->start_date->format('M j, Y') }}</p></div><span class="hd-pct">{{ $app->employee->progress() }}%</span></div>
                    <span class="hd-progress"><span style="width: {{ $app->employee->progress() }}%"></span></span>
                    <div class="hd-row-gap hd-mt"><a class="hd-btn hd-btn-soft" href="{{ EmployeeResource::getUrl('view', ['record' => $app->employee]) }}">Open onboarding</a></div>
                </section>
            @endif

            <section class="hd-card">
                <div class="hd-card-head"><div><h3>Notes</h3><p>Visible to the hiring team only</p></div></div>
                <form wire:submit="addNote" class="hd-note-form">
                    <textarea wire:model="note" rows="3" class="hd-input" placeholder="Add a private note… (@mention a colleague)"></textarea>
                    @error('note')<p class="hd-error">{{ $message }}</p>@enderror
                    <button type="submit" class="hd-btn hd-btn-primary">Add note</button>
                </form>
                <ul class="hd-notes">
                    @foreach ($app->notes as $note)
                        <li><p>{{ $note->body }}</p><small>{{ $note->user?->name }} · {{ $note->created_at->diffForHumans() }}</small></li>
                    @endforeach
                </ul>
            </section>

            <section class="hd-card">
                <div class="hd-card-head"><div><h3>Activity</h3></div></div>
                <ul class="hd-timeline">
                    @foreach ($app->activities->take(15) as $activity)
                        <li>
                            <span class="hd-timeline-dot hd-type-{{ $activity->type }}"></span>
                            <div><p>{{ $activity->description }}</p><small>{{ $activity->created_at->format('M j, g:i A') }}@if($activity->user) · {{ $activity->user->name }}@endif</small></div>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</x-filament-panels::page>
