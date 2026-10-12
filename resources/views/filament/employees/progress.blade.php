@php
    $e = $getRecord()->loadMissing('tasks');
    $p = $e->progress();
    $groups = $e->tasks->groupBy(fn ($t) => $t->assignee->getLabel());
@endphp
<div class="hd-onb">
    <div class="hd-onb-ring" style="--p: {{ $p }}">
        <span><b>{{ $p }}%</b><small>complete</small></span>
    </div>
    <div class="hd-onb-groups">
        @foreach ($groups as $label => $tasks)
            @php $done = $tasks->whereNotNull('completed_at')->count(); @endphp
            <div class="hd-onb-group">
                <div class="hd-onb-group-head"><b>{{ $label }}</b><span>{{ $done }}/{{ $tasks->count() }}</span></div>
                <span class="hd-progress"><span style="width: {{ $tasks->count() ? $done / $tasks->count() * 100 : 0 }}%"></span></span>
            </div>
        @endforeach
        <p class="hd-muted hd-small">Portal: <a class="hd-link" href="{{ $e->portalUrl() }}" target="_blank">{{ \Illuminate\Support\Str::limit($e->portalUrl(), 60) }}</a></p>
    </div>
</div>
