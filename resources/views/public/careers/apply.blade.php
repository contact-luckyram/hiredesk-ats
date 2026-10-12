@extends('public.layout')

@section('title', 'Apply: '.$job->title)

@section('content')
    <section class="j-head j-head-sm">
        <div class="s-wrap">
            <a href="{{ route('careers.show', $job) }}" class="j-back">← Back to job</a>
            <h1>Apply for {{ $job->title }}</h1>
            <p class="c-meta"><span>📍 {{ $job->locationLabel() }}</span><span>{{ $job->employment_type->getLabel() }}</span>@if($job->salaryLabel())<span>{{ $job->salaryLabel() }}</span>@endif</p>
        </div>
    </section>

    <section class="s-wrap a-wrap">
        <form method="POST" action="{{ route('careers.store', $job) }}" enctype="multipart/form-data" class="a-form" novalidate>
            @csrf
            <input type="hidden" name="source" value="{{ request('source') }}">

            @if ($errors->any())
                <div class="c-alert c-alert-error" role="alert">Please fix the {{ $errors->count() }} highlighted {{ \Illuminate\Support\Str::plural('field', $errors->count()) }} below.</div>
            @endif

            <fieldset class="a-card">
                <legend><span>1</span> Your details</legend>
                <div class="a-grid">
                    @foreach ([['first_name', 'First name', 'text', true, 'given-name'], ['last_name', 'Last name', 'text', true, 'family-name'], ['email', 'Email', 'email', true, 'email'], ['phone', 'Mobile phone', 'tel', false, 'tel'], ['city', 'City', 'text', false, 'address-level2'], ['zip', 'ZIP code', 'text', false, 'postal-code']] as [$name, $label, $type, $required, $auto])
                        <label class="a-field @error($name) has-error @enderror">
                            <span>{{ $label }}@if($required)<em>*</em>@endif</span>
                            <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name) }}" autocomplete="{{ $auto }}" @if($required) required @endif>
                            @error($name)<small>{{ $message }}</small>@enderror
                        </label>
                    @endforeach
                    <input type="hidden" name="state" value="MA">
                    <label class="a-field a-span-2 @error('linkedin_url') has-error @enderror">
                        <span>LinkedIn profile (optional)</span>
                        <input type="url" name="linkedin_url" value="{{ old('linkedin_url') }}" placeholder="https://linkedin.com/in/…">
                        @error('linkedin_url')<small>{{ $message }}</small>@enderror
                    </label>
                </div>
                <label class="c-check a-mt"><input type="hidden" name="sms_opt_in" value="0"><input type="checkbox" name="sms_opt_in" value="1" @checked(old('sms_opt_in', true))> It's OK to text me about my application</label>
            </fieldset>

            <fieldset class="a-card">
                <legend><span>2</span> Resume</legend>
                <label class="a-drop @error('resume') has-error @enderror">
                    <input type="file" name="resume" accept=".pdf,.docx,.txt" data-label="resume-name">
                    <b>📎 Upload your resume</b>
                    <span id="resume-name">PDF, DOCX or TXT · up to 5 MB</span>
                </label>
                @error('resume')<small class="a-error">{{ $message }}</small>@enderror
                <label class="a-field a-mt">
                    <span>Cover letter (optional)</span>
                    <textarea name="cover_letter" rows="4" placeholder="Tell us why you're a great fit…">{{ old('cover_letter') }}</textarea>
                </label>
            </fieldset>

            @if ($job->questionnaire && count($job->questionnaire->questions ?? []))
                <fieldset class="a-card">
                    <legend><span>3</span> A few quick questions</legend>
                    @foreach ($job->questionnaire->questions as $q)
                        @php $key = $q['key'] ?? \Illuminate\Support\Str::slug($q['label'], '_'); $field = "answers.$key"; @endphp
                        <div class="a-question @error($field) has-error @enderror">
                            <p>{{ $q['label'] }}@if(($q['type'] ?? 'yes_no') !== 'text')<em>*</em>@endif</p>
                            @switch($q['type'] ?? 'yes_no')
                                @case('yes_no')
                                    <div class="a-pills">
                                        @foreach (['yes' => 'Yes', 'no' => 'No'] as $v => $l)
                                            <label><input type="radio" name="answers[{{ $key }}]" value="{{ $v }}" @checked(old("answers.$key") === $v)><span>{{ $l }}</span></label>
                                        @endforeach
                                    </div>
                                    @break
                                @case('choice')
                                    <div class="a-pills">
                                        @foreach ($q['options'] ?? [] as $option)
                                            <label><input type="radio" name="answers[{{ $key }}]" value="{{ $option }}" @checked(old("answers.$key") === $option)><span>{{ $option }}</span></label>
                                        @endforeach
                                    </div>
                                    @break
                                @case('number')
                                    <input type="number" name="answers[{{ $key }}]" value="{{ old("answers.$key") }}" min="0" max="99" class="a-input-sm">
                                    @break
                                @default
                                    <textarea name="answers[{{ $key }}]" rows="3">{{ old("answers.$key") }}</textarea>
                            @endswitch
                            @error($field)<small>{{ $message }}</small>@enderror
                        </div>
                    @endforeach
                </fieldset>
            @endif

            <fieldset class="a-card">
                <legend><span>{{ $job->questionnaire ? 4 : 3 }}</span> Voluntary self-identification</legend>
                <p class="a-help">{{ config('hiredesk.company') }} is an equal opportunity employer. Answers are optional, kept separate from your application and never used in hiring decisions.</p>
                <div class="a-grid">
                    @foreach ([
                        'gender' => ['Gender', ['Female', 'Male', 'Non-binary', 'Decline to answer']],
                        'race' => ['Race / ethnicity', ['Hispanic or Latino', 'White', 'Black or African American', 'Asian', 'Native Hawaiian or Pacific Islander', 'American Indian or Alaska Native', 'Two or more races', 'Decline to answer']],
                        'veteran' => ['Veteran status', ['Protected veteran', 'Not a protected veteran', 'Decline to answer']],
                    ] as $name => [$label, $options])
                        <label class="a-field">
                            <span>{{ $label }}</span>
                            <select name="eeo[{{ $name }}]">
                                <option value="">Select…</option>
                                @foreach ($options as $o)<option @selected(old("eeo.$name") === $o)>{{ $o }}</option>@endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="a-submit">
                <label class="c-check @error('consent') has-error @enderror"><input type="checkbox" name="consent" value="1" @checked(old('consent'))> I confirm the information above is accurate.</label>
                @error('consent')<small class="a-error">{{ $message }}</small>@enderror
                <button class="s-btn s-btn-brand s-btn-lg">Submit application</button>
            </div>
        </form>

        <aside class="a-aside">
            <div class="j-card">
                <h3>{{ $job->title }}</h3>
                <p class="c-summary">{{ $job->summary }}</p>
                <ul class="a-steps">
                    <li>We review every application within 3 business days.</li>
                    <li>You'll get updates by email{{ ' and text' }} as you move forward.</li>
                    <li>Questions? Reply to any email from us.</li>
                </ul>
            </div>
        </aside>
    </section>
@endsection
