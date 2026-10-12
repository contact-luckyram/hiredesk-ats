<div class="hd-stage-chips">
    @foreach ($getRecord()->stages as $stage)
        <span class="hd-stage-chip" style="--c: {{ $stage->color }}" title="{{ $stage->category->getLabel() }}{{ $stage->sla_days ? ' · SLA '.$stage->sla_days.'d' : '' }}">
            <i></i>{{ $stage->name }}@if(count($stage->automations ?? []))<em>⚡{{ count($stage->automations) }}</em>@endif
        </span>
        @if (! $loop->last)<span class="hd-stage-arrow">›</span>@endif
    @endforeach
</div>
