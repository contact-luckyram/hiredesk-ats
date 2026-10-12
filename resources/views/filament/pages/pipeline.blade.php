@php
    use App\Filament\Resources\Applications\ApplicationResource;
    use App\Filament\Resources\Candidates\CandidateResource;
@endphp

<x-filament-panels::page>
    <div class="hd-toolbar">
        <label class="hd-field">
            <span>Job</span>
            <select wire:model.live="job" class="hd-select">
                @foreach ($jobOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->title }}{{ $option->status->value !== 'published' ? ' ('.$option->status->getLabel().')' : '' }}</option>
                @endforeach
            </select>
        </label>
        <label class="hd-field hd-grow">
            <span>Search</span>
            <input type="search" wire:model.live.debounce.400ms="search" placeholder="Name or email…" class="hd-input">
        </label>
        <label class="hd-switch">
            <input type="checkbox" wire:model.live="showRejected">
            <span class="hd-switch-ui"></span>
            Show not selected ({{ $totals['rejected'] }})
        </label>
        <div class="hd-toolbar-stat"><b>{{ $totals['active'] }}</b> active candidates</div>
    </div>

    @if (! $currentJob)
        <div class="hd-card hd-empty">Create a job to start building a pipeline.</div>
    @else
        <div
            class="hd-board"
            x-data="{ dragging: null, over: null }"
            wire:loading.class="is-busy"
        >
            @foreach ($stages as $stage)
                @php $cards = $applications[$stage->id] ?? collect(); @endphp
                <section
                    class="hd-col"
                    style="--c: {{ $stage->color }}"
                    x-on:dragover.prevent="over = {{ $stage->id }}"
                    x-on:dragleave="over = null"
                    x-on:drop.prevent="over = null; if (dragging) { $wire.moveApplication(dragging, {{ $stage->id }}) }; dragging = null"
                    x-bind:class="{ 'is-over': over === {{ $stage->id }} }"
                    wire:key="stage-{{ $stage->id }}"
                >
                    <header class="hd-col-head">
                        <span class="hd-col-name"><i></i>{{ $stage->name }}</span>
                        <span class="hd-col-count">{{ $cards->count() }}</span>
                    </header>
                    <div class="hd-col-meta">
                        @if ($stage->sla_days)<span title="Stage SLA">⏱ {{ $stage->sla_days }}d SLA</span>@endif
                        @if (count($stage->automations ?? []))<span title="Automations run when a candidate enters this stage">⚡ {{ count($stage->automations) }}</span>@endif
                    </div>

                    <div class="hd-col-body">
                        @forelse ($cards as $app)
                            <article
                                class="hd-kcard {{ $app->knocked_out ? 'is-knocked' : '' }}"
                                draggable="true"
                                x-on:dragstart="dragging = {{ $app->id }}; $event.dataTransfer.effectAllowed = 'move'"
                                x-on:dragend="dragging = null"
                                x-bind:class="{ 'is-dragging': dragging === {{ $app->id }} }"
                                wire:key="app-{{ $app->id }}"
                            >
                                <a href="{{ ApplicationResource::getUrl('view', ['record' => $app]) }}" class="hd-kcard-link">
                                    <span class="hd-avatar hd-avatar-sm" style="--c: {{ $stage->color }}">{{ $app->candidate->initials() }}</span>
                                    <span class="hd-kcard-main">
                                        <b>{{ $app->candidate->name }}</b>
                                        <small>{{ $app->source }} · {{ $app->applied_at?->diffForHumans(short: true) }}</small>
                                    </span>
                                    @if ($app->score !== null)
                                        <span class="hd-score hd-score-{{ $app->scoreColor() }}" title="Screening score">{{ $app->score }}</span>
                                    @endif
                                </a>
                                <div class="hd-kcard-foot">
                                    <span class="hd-stars" aria-label="Rating {{ $app->rating ?? 0 }} of 5">@for ($i = 1; $i <= 5; $i++)<i @class(['on' => $i <= ($app->rating ?? 0)])>★</i>@endfor</span>
                                    <span class="hd-kcard-icons">
                                        @if ($app->knocked_out)<span class="hd-pill hd-pill-rose">Knock-out</span>@endif
                                        @if ($app->isOverSla())<span class="hd-pill hd-pill-amber" title="Past stage SLA">{{ $app->daysInStage() }}d</span>@endif
                                        @if ($app->interviews_count)<span title="Interviews">📅 {{ $app->interviews_count }}</span>@endif
                                        @if ($app->scorecards_count)<span title="Scorecards">📝 {{ $app->scorecards_count }}</span>@endif
                                    </span>
                                </div>
                                <label class="hd-move">
                                    <span class="sr-only">Move to stage</span>
                                    <select x-on:change="$wire.moveApplication({{ $app->id }}, parseInt($event.target.value))">
                                        <option value="">Move to…</option>
                                        @foreach ($allStages as $target)
                                            @if ($target->id !== $stage->id)<option value="{{ $target->id }}">{{ $target->name }}</option>@endif
                                        @endforeach
                                    </select>
                                </label>
                            </article>
                        @empty
                            <div class="hd-col-empty">Drop candidates here</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
        <p class="hd-muted hd-small hd-hint">Tip: drag a card to another stage, or use “Move to…” on touch screens. Stage automations (emails, texts, offers, onboarding) run automatically.</p>
    @endif
</x-filament-panels::page>
