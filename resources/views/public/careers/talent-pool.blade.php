@extends('public.layout')

@section('title', 'Join our talent pool')

@section('content')
    <section class="j-head j-head-sm">
        <div class="s-wrap">
            <h1>Join our talent pool</h1>
            <p class="c-meta"><span>Don't see the right role yet? Tell us what you're looking for and we'll reach out first.</span></p>
        </div>
    </section>
    <section class="s-wrap a-wrap a-wrap-narrow">
        <form method="POST" action="{{ route('careers.talent-pool.store') }}" class="a-form">
            @csrf
            <fieldset class="a-card">
                <div class="a-grid">
                    @foreach ([['first_name', 'First name', 'text', true], ['last_name', 'Last name', 'text', true], ['email', 'Email', 'email', true], ['phone', 'Mobile phone', 'tel', false], ['zip', 'ZIP code', 'text', false]] as [$name, $label, $type, $required])
                        <label class="a-field @error($name) has-error @enderror">
                            <span>{{ $label }}@if($required)<em>*</em>@endif</span>
                            <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name) }}" @if($required) required @endif>
                            @error($name)<small>{{ $message }}</small>@enderror
                        </label>
                    @endforeach
                </div>
                <p class="a-label a-mt">Areas of interest</p>
                <div class="a-pills a-pills-multi">
                    @foreach ($departments as $d)
                        <label><input type="checkbox" name="interests[]" value="{{ $d->name }}" @checked(in_array($d->name, old('interests', [])))><span>{{ $d->name }}</span></label>
                    @endforeach
                </div>
            </fieldset>
            <div class="a-submit"><button class="s-btn s-btn-brand s-btn-lg">Join talent pool</button></div>
        </form>
    </section>
@endsection
