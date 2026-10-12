<?php

use App\Filament\Pages\Pipeline;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Candidates\CandidateResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\JobOpenings\JobOpeningResource;
use App\Filament\Resources\Offers\OfferResource;
use App\Filament\Resources\Workflows\WorkflowResource;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\JobOpening;
use App\Models\Offer;
use App\Models\Workflow;
use Illuminate\Support\Facades\Artisan;

// Lists the key demo URLs (used by the screenshot smoke test and handy for demos).
Artisan::command('hiredesk:demo-urls', function () {
    $job = JobOpening::where('title', 'Registered Nurse – ICU')->first() ?? JobOpening::published()->first();
    $maria = Application::whereHas('candidate', fn ($q) => $q->where('first_name', 'Maria')->where('last_name', 'Lopez'))->first() ?? Application::first();
    $employee = Employee::where('status', 'onboarding')->orderBy('start_date')->first() ?? Employee::first();
    $offer = Offer::where('status', 'sent')->first() ?? Offer::first();

    $urls = [
        'landing' => route('home', absolute: false),
        'careers' => route('careers.index', absolute: false),
        'careers-search' => route('careers.index', ['q' => 'nurse', 'zip' => '02169', 'miles' => 25], false),
        'job' => route('careers.show', $job, false),
        'apply' => route('careers.apply', $job, false),
        'talent-pool' => route('careers.talent-pool', absolute: false),
        'offer-sign' => route('offers.show', $offer->token, false),
        'portal' => route('portal.show', $employee->portal_token, false),
        'admin-dashboard' => '/admin',
        'admin-pipeline' => Pipeline::getUrl(['job' => $job->id], false),
        'admin-candidate-profile' => ApplicationResource::getUrl('view', ['record' => $maria], false),
        'admin-applications' => ApplicationResource::getUrl(isAbsolute: false),
        'admin-jobs' => JobOpeningResource::getUrl(isAbsolute: false),
        'admin-job-edit' => JobOpeningResource::getUrl('edit', ['record' => $job], false),
        'admin-candidates' => CandidateResource::getUrl(isAbsolute: false),
        'admin-candidate' => CandidateResource::getUrl('view', ['record' => Candidate::first()], false),
        'admin-stages' => WorkflowResource::getUrl('edit', ['record' => Workflow::first()], false),
        'admin-workflows' => WorkflowResource::getUrl(isAbsolute: false),
        'admin-interviews' => '/admin/interviews',
        'admin-offers' => OfferResource::getUrl(isAbsolute: false),
        'admin-offer' => OfferResource::getUrl('edit', ['record' => $offer], false),
        'admin-employees' => EmployeeResource::getUrl(isAbsolute: false),
        'admin-employee' => EmployeeResource::getUrl('view', ['record' => $employee], false),
        'admin-reports' => '/admin/reports',
        'admin-questionnaires' => '/admin/questionnaires',
        'admin-onboarding-templates' => '/admin/onboarding-templates',
        'admin-templates' => '/admin/email-templates',
        'admin-team' => '/admin/users',
    ];

    $this->line(json_encode($urls, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
})->purpose('Print the main demo URLs as JSON');
