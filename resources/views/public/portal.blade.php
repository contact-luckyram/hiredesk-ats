@extends('public.layout')

@section('title', 'Welcome, '.$employee->first_name.' · New-hire portal')
@section('no_footer', true)

@section('nav')
    <header class="s-nav">
        <div class="s-wrap s-nav-inner">
            <span class="s-logo"><span class="s-logo-mark">{{ mb_substr(config('hiredesk.company'), 0, 1) }}</span><span>{{ config('hiredesk.company') }}</span></span>
            <span class="p-nav-label s-hide-sm">New-hire portal</span>
            <span class="p-avatar">{{ $employee->initials() }}</span>
        </div>
    </header>
@endsection

@section('content')
    <section class="s-wrap p-wrap">
        @if (session('status'))<div class="c-alert c-alert-success">✅ {{ session('status') }}</div>@endif

        <div class="p-grid">
            <div class="p-main">
                <div class="a-card p-welcome">
                    <h1>Welcome, {{ $employee->first_name }}! 👋</h1>
                    <p>{{ $employee->job_title }} · {{ $employee->location?->label() ?? '' }} · Start date: <b>{{ $employee->start_date->format('l, F j') }}</b></p>
                    <div class="p-progress-head"><b>Your onboarding</b><span>{{ $tasks->filter->isDone()->count() }} of {{ $tasks->count() }} done</span></div>
                    <span class="p-progress"><span style="width: {{ $progress }}%"></span></span>
                    @if ($progress === 100)<p class="p-done">🎉 All set! We can't wait to see you on day one.</p>@endif
                </div>

                <div class="a-card p-tasks">
                    <h2>Your tasks</h2>
                    @foreach ($tasks as $task)
                        <a href="{{ route('portal.show', ['token' => $employee->portal_token, 'task' => $task->id]) }}#task" @class(['p-task', 'is-done' => $task->isDone(), 'is-active' => $active?->id === $task->id])>
                            <span class="p-check">{{ $task->isDone() ? '✓' : '' }}</span>
                            <span class="p-task-main">
                                <b>{{ $task->title }}</b>
                                <small>
                                    @if ($task->isDone()) Completed {{ $task->completed_at->format('M j') }}
                                    @else {{ $task->type->getLabel() }}{{ $task->due_date ? ' · due '.$task->due_date->format('M j') : '' }}
                                    @endif
                                </small>
                            </span>
                            @if ($task->isDone())<span class="p-pill p-pill-done">Done</span>
                            @elseif ($task->isOverdue())<span class="p-pill p-pill-late">Overdue</span>
                            @else<span class="p-pill">To do</span>@endif
                        </a>
                    @endforeach
                </div>
            </div>

            <aside class="p-side" id="task">
                @if ($active)
                    <div class="a-card p-active">
                        <span class="p-type">{{ $active->type->getLabel() }}</span>
                        <h2>{{ $active->title }}</h2>
                        @if ($active->description)<p class="a-help">{{ $active->description }}</p>@endif

                        @if ($active->isDone())
                            <p class="c-alert c-alert-success">This task is complete. You can update it below if something changed.</p>
                        @endif

                        <form method="POST" action="{{ route('portal.complete', [$employee->portal_token, $active]) }}" enctype="multipart/form-data">
                            @csrf
                            @switch($active->type->value)
                                @case('form')
                                    @foreach ($active->data['fields'] ?? [] as $field)
                                        @php $name = 'values.'.$field['name']; $value = old($name, $active->data['values'][$field['name']] ?? ''); @endphp
                                        <label class="a-field @error($name) has-error @enderror">
                                            <span>{{ $field['label'] }}@if($field['required'] ?? false)<em>*</em>@endif</span>
                                            @if (($field['type'] ?? 'text') === 'textarea')
                                                <textarea name="values[{{ $field['name'] }}]" rows="2">{{ $value }}</textarea>
                                            @elseif (($field['type'] ?? 'text') === 'select')
                                                <select name="values[{{ $field['name'] }}]">
                                                    <option value="">Select…</option>
                                                    @foreach (array_map('trim', explode(',', $field['options'] ?? '')) as $opt)<option @selected($value === $opt)>{{ $opt }}</option>@endforeach
                                                </select>
                                            @else
                                                <input type="{{ $field['type'] ?? 'text' }}" name="values[{{ $field['name'] }}]" value="{{ $value }}">
                                            @endif
                                            @error($name)<small>{{ $message }}</small>@enderror
                                        </label>
                                    @endforeach
                                    <button class="s-btn s-btn-brand s-btn-block">Save &amp; continue</button>
                                    @break

                                @case('document')
                                    <div class="p-doc">{!! nl2br(e($active->data['document'] ?? $active->description)) !!}</div>
                                    @if ($active->isDone() && $active->signed_name)
                                        <p>Signed by <b>{{ $active->signed_name }}</b> on {{ $active->completed_at->format('M j, Y') }}.</p>
                                    @else
                                        <label class="a-field @error('signed_name') has-error @enderror">
                                            <span>Full legal name<em>*</em></span>
                                            <input type="text" name="signed_name" value="{{ old('signed_name', $employee->name) }}">
                                            @error('signed_name')<small>{{ $message }}</small>@enderror
                                        </label>
                                        <div class="a-field @error('signature') has-error @enderror">
                                            <span>Signature<em>*</em></span>
                                            <canvas class="sig-pad" data-signature="task-signature" aria-label="Signature pad"></canvas>
                                            <input type="hidden" name="signature" id="task-signature">
                                            <button type="button" class="sig-clear" data-clear="task-signature">Clear</button>
                                            @error('signature')<small>{{ $message }}</small>@enderror
                                        </div>
                                        <label class="c-check"><input type="checkbox" name="agree" value="1"> I have read and agree to this document.</label>
                                        @error('agree')<small class="a-error">{{ $message }}</small>@enderror
                                        <button class="s-btn s-btn-brand s-btn-block">Adopt &amp; sign</button>
                                    @endif
                                    @break

                                @case('upload')
                                    <label class="a-drop @error('file') has-error @enderror">
                                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.heic,.docx" data-label="upload-name" capture="environment">
                                        <b>📷 Take a photo or choose a file</b>
                                        <span id="upload-name">{{ $active->file_path ? 'File received — upload again to replace' : 'PDF, JPG or PNG · up to 8 MB' }}</span>
                                    </label>
                                    @error('file')<small class="a-error">{{ $message }}</small>@enderror
                                    <button class="s-btn s-btn-brand s-btn-block a-mt">Upload</button>
                                    @break

                                @case('training')
                                    <div class="p-video" role="img" aria-label="Training video placeholder"><span>▶</span><small>25-minute course</small></div>
                                    <button class="s-btn s-btn-brand s-btn-block">I've completed this training</button>
                                    @break

                                @default
                                    <button class="s-btn s-btn-brand s-btn-block">Mark as done</button>
                            @endswitch
                        </form>
                    </div>
                @else
                    <div class="a-card p-active"><h2>You're all done 🎉</h2><p>Every task is complete. See you on {{ $employee->start_date->format('F j') }}!</p></div>
                @endif

                <div class="a-card">
                    <h3>Your first day</h3>
                    <dl class="p-dl">
                        <dt>Report to</dt><dd>{{ $employee->manager_name ?? 'Human Resources' }}</dd>
                        <dt>Arrive</dt><dd>8:30 AM, main lobby</dd>
                        <dt>Where</dt><dd>{{ $employee->location?->name ?? 'Remote — video link to follow' }}</dd>
                        <dt>Bring</dt><dd>Photo ID and any licences</dd>
                    </dl>
                </div>

                @if ($teamTasks->count())
                    <div class="a-card">
                        <h3>Your team is getting ready</h3>
                        <ul class="p-team">
                            @foreach ($teamTasks as $t)
                                <li @class(['is-done' => $t->isDone()])><span>{{ $t->isDone() ? '✓' : '•' }}</span>{{ $t->title }} <small>{{ $t->assignee->getLabel() }}</small></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </aside>
        </div>
    </section>
@endsection
