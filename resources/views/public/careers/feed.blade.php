{!! '<'.'?xml version="1.0" encoding="utf-8"?>' !!}
<source>
    <publisher>{{ config('hiredesk.company') }}</publisher>
    <publisherurl>{{ route('careers.index') }}</publisherurl>
    <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
@foreach ($jobs as $job)
    <job>
        <title><![CDATA[{{ $job->title }}]]></title>
        <date><![CDATA[{{ $job->published_at?->toRfc2822String() }}]]></date>
        <referencenumber><![CDATA[{{ $job->reference }}]]></referencenumber>
        <url><![CDATA[{{ route('careers.show', $job) }}?source=indeed]]></url>
        <company><![CDATA[{{ config('hiredesk.company') }}]]></company>
        <city><![CDATA[{{ $job->location?->city }}]]></city>
        <state><![CDATA[{{ $job->location?->state }}]]></state>
        <country><![CDATA[US]]></country>
        <postalcode><![CDATA[{{ $job->location?->zip }}]]></postalcode>
        <description><![CDATA[{!! $job->description !!}]]></description>
        <salary><![CDATA[{{ $job->salaryLabel() }}]]></salary>
        <jobtype><![CDATA[{{ $job->employment_type->getLabel() }}]]></jobtype>
        <category><![CDATA[{{ $job->department?->name }}]]></category>
        <remotetype><![CDATA[{{ $job->workplace->value === 'remote' ? 'Fully remote' : '' }}]]></remotetype>
    </job>
@endforeach
</source>
