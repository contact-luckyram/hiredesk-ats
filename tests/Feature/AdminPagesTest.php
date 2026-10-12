<?php

namespace Tests\Feature;

use App\Filament\Pages\Pipeline;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Candidates\CandidateResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Filament\Resources\EmailTemplates\EmailTemplateResource;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Filament\Resources\Interviews\InterviewResource;
use App\Filament\Resources\JobOpenings\JobOpeningResource;
use App\Filament\Resources\Locations\LocationResource;
use App\Filament\Resources\Offers\OfferResource;
use App\Filament\Resources\OnboardingTemplates\OnboardingTemplateResource;
use App\Filament\Resources\Questionnaires\QuestionnaireResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Workflows\WorkflowResource;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\Interview;
use App\Models\JobOpening;
use App\Models\Message;
use App\Models\Offer;
use App\Models\OnboardingTemplate;
use App\Models\Questionnaire;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::where('email', 'admin@hiredesk.test')->first());
    }

    public function test_demo_data_is_seeded(): void
    {
        $this->assertGreaterThanOrEqual(10, JobOpening::count());
        $this->assertGreaterThan(100, Application::count());
        $this->assertSame(4, Workflow::count());
        $this->assertGreaterThanOrEqual(3, Employee::where('status', 'onboarding')->count());
        $this->assertNotNull(Offer::where('status', 'sent')->first());
    }

    public function test_dashboard_pipeline_and_reports_render(): void
    {
        $this->get('/admin')->assertOk()->assertSee('Applications &amp; hires', false)->assertSee('Upcoming interviews');
        $this->get(Pipeline::getUrl())->assertOk()->assertSee('Hiring pipeline');
        $this->get('/admin/reports')->assertOk()->assertSee('Source effectiveness');
        $this->get('/admin/reports?range=30')->assertOk();
    }

    public function test_every_resource_index_renders(): void
    {
        foreach ([
            JobOpeningResource::class, CandidateResource::class, ApplicationResource::class, InterviewResource::class,
            OfferResource::class, QuestionnaireResource::class, EmployeeResource::class, OnboardingTemplateResource::class,
            WorkflowResource::class, EmailTemplateResource::class, DepartmentResource::class, LocationResource::class, UserResource::class,
        ] as $resource) {
            $this->get($resource::getUrl())->assertOk();
        }
    }

    public function test_create_and_edit_pages_render(): void
    {
        $this->get(JobOpeningResource::getUrl('create'))->assertOk();
        $this->get(JobOpeningResource::getUrl('edit', ['record' => JobOpening::first()]))->assertOk();
        $this->get(CandidateResource::getUrl('create'))->assertOk();
        $this->get(CandidateResource::getUrl('view', ['record' => Candidate::first()]))->assertOk();
        $this->get(CandidateResource::getUrl('edit', ['record' => Candidate::first()]))->assertOk();
        $this->get(WorkflowResource::getUrl('edit', ['record' => Workflow::first()]))->assertOk()->assertSee('Pipeline stages');
        $this->get(WorkflowResource::getUrl('create'))->assertOk();
        $this->get(QuestionnaireResource::getUrl('edit', ['record' => Questionnaire::first()]))->assertOk();
        $this->get(OnboardingTemplateResource::getUrl('edit', ['record' => OnboardingTemplate::first()]))->assertOk();
        $this->get(OfferResource::getUrl('edit', ['record' => Offer::first()]))->assertOk();
        $this->get(EmployeeResource::getUrl('view', ['record' => Employee::first()]))->assertOk();
        $this->get(EmployeeResource::getUrl('create'))->assertOk();
    }

    public function test_candidate_profile_renders_for_every_stage_type(): void
    {
        foreach (Application::with('stage')->get()->unique(fn ($a) => $a->stage?->category?->value) as $application) {
            $this->get(ApplicationResource::getUrl('view', ['record' => $application]))->assertOk()->assertSee($application->candidate->name);
        }
    }

    public function test_moving_a_card_runs_stage_automations(): void
    {
        $job = JobOpening::where('title', 'Registered Nurse – ICU')->first();
        $application = Application::where('job_opening_id', $job->id)->whereHas('stage', fn ($q) => $q->where('name', 'Screening'))->first();
        $interview = $job->workflow->stages->firstWhere('name', 'Interview');
        $before = Message::where('application_id', $application->id)->count();

        Livewire::test(Pipeline::class, ['job' => $job->id])
            ->call('moveApplication', $application->id, $interview->id)
            ->assertHasNoErrors();

        $this->assertSame($interview->id, $application->fresh()->stage_id);
        $this->assertGreaterThan($before, Message::where('application_id', $application->id)->count());
    }

    public function test_moving_to_offer_drafts_an_offer_and_hiring_starts_onboarding(): void
    {
        $job = JobOpening::where('title', 'Registered Nurse – ICU')->first();
        $application = Application::where('job_opening_id', $job->id)->whereHas('stage', fn ($q) => $q->where('name', 'Interview'))->doesntHave('offer')->first();

        Livewire::test(Pipeline::class, ['job' => $job->id])
            ->call('moveApplication', $application->id, $job->workflow->stages->firstWhere('name', 'Offer')->id);
        $this->assertNotNull($application->fresh()->offer);

        Livewire::test(Pipeline::class, ['job' => $job->id])
            ->call('moveApplication', $application->id, $job->workflow->stages->firstWhere('name', 'Hired')->id);
        $employee = $application->fresh()->employee;
        $this->assertNotNull($employee);
        $this->assertGreaterThan(5, $employee->tasks()->count());
        $this->get($employee->portalUrl())->assertOk()->assertSee('Welcome, '.$employee->first_name);
    }

    public function test_ics_and_csv_exports(): void
    {
        $this->get(route('interviews.ics', Interview::first()))->assertOk()->assertSee('BEGIN:VCALENDAR');
        $this->get(route('reports.export'))->assertOk();
    }
}
