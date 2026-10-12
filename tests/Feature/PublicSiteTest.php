<?php

namespace Tests\Feature;

use App\Enums\OfferStatus;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\JobOpening;
use App\Models\Offer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private const SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    public function test_landing_and_careers_pages(): void
    {
        $this->get('/')->assertOk()->assertSee('Open the live demo');
        $this->get('/careers')->assertOk()->assertSee('Registered Nurse – ICU');
        $this->get('/careers?q=nurse&zip=02169&miles=25')->assertOk()->assertSee('mi');
        $this->get('/careers?zip=99999')->assertOk()->assertSee('couldn');
        $this->get('/careers/talent-pool')->assertOk();
        $this->get('/jobs.xml')->assertOk()->assertSee('<source>', false);

        $job = JobOpening::published()->first();
        $this->get(route('careers.show', $job))->assertOk()->assertSee($job->title);
        $this->get(route('careers.apply', $job))->assertOk()->assertSee('Submit application');
    }

    public function test_unpublished_jobs_are_hidden(): void
    {
        $draft = JobOpening::where('status', 'draft')->first();
        $this->get(route('careers.show', $draft))->assertNotFound();
    }

    public function test_applying_scores_the_questionnaire_and_parses_the_resume(): void
    {
        $job = JobOpening::where('title', 'Registered Nurse – ICU')->first();
        $resume = UploadedFile::fake()->createWithContent('resume.txt', "Jane Doe\n8 years ICU nurse. ACLS, BLS, Epic, Ventilator management.");

        $this->post(route('careers.store', $job), [
            'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane.doe@example.org', 'phone' => '(617) 555-0100',
            'zip' => '02115', 'resume' => $resume, 'consent' => '1', 'sms_opt_in' => '1',
            'answers' => ['licence' => 'yes', 'years' => '8', 'shifts' => 'Any shift', 'acls' => 'yes', 'why' => 'I love it'],
            'eeo' => ['gender' => 'Female'],
        ])->assertRedirect(route('careers.thanks'));

        $application = Application::whereHas('candidate', fn ($q) => $q->where('email', 'jane.doe@example.org'))->first();
        $this->assertNotNull($application);
        $this->assertGreaterThanOrEqual(90, $application->score);
        $this->assertFalse($application->knocked_out);
        $this->assertSame('New', $application->stage->name);
        $this->assertContains('ACLS', $application->candidate->skills);
        $this->assertTrue($application->messages()->exists(), 'Auto-reply email was not logged');
    }

    public function test_knockout_answer_is_flagged(): void
    {
        $job = JobOpening::where('title', 'Registered Nurse – ICU')->first();

        $this->post(route('careers.store', $job), [
            'first_name' => 'Ko', 'last_name' => 'Test', 'email' => 'ko@example.org', 'consent' => '1',
            'answers' => ['licence' => 'no', 'years' => '0', 'shifts' => 'Days', 'acls' => 'no'],
        ])->assertRedirect();

        $this->assertTrue(Application::whereHas('candidate', fn ($q) => $q->where('email', 'ko@example.org'))->first()->knocked_out);
    }

    public function test_apply_validation_errors(): void
    {
        $job = JobOpening::where('title', 'Registered Nurse – ICU')->first();
        $this->from(route('careers.apply', $job))->post(route('careers.store', $job), [])
            ->assertRedirect(route('careers.apply', $job))
            ->assertSessionHasErrors(['first_name', 'email', 'consent', 'answers.licence']);
    }

    public function test_talent_pool_signup(): void
    {
        $this->post(route('careers.talent-pool.store'), ['first_name' => 'Pat', 'last_name' => 'Lee', 'email' => 'pat@example.org', 'interests' => ['Nursing']])
            ->assertRedirect(route('careers.thanks'));
        $this->assertTrue(Candidate::where('email', 'pat@example.org')->first()->in_talent_pool);
    }

    public function test_candidate_can_sign_an_offer(): void
    {
        $offer = Offer::where('status', OfferStatus::Sent->value)->first();
        $this->get(route('offers.show', $offer->token))->assertOk()->assertSee('Accept offer');

        $this->post(route('offers.sign', $offer->token), ['signed_name' => 'Maria Lopez', 'signature' => self::SIGNATURE, 'agree' => '1'])
            ->assertRedirect(route('offers.show', $offer->token));

        $this->assertSame(OfferStatus::Signed, $offer->fresh()->status);
        $this->get(route('offers.show', $offer->token))->assertOk()->assertSee('Accepted electronically');
    }

    public function test_new_hire_completes_portal_tasks(): void
    {
        $employee = Employee::where('status', 'onboarding')->first();
        $this->get($employee->portalUrl())->assertOk()->assertSee('Your tasks');

        $form = $employee->tasks()->where('type', 'form')->where('title', 'Emergency contacts')->first();
        $this->post(route('portal.complete', [$employee->portal_token, $form]), ['values' => ['contact_name' => 'Sam', 'relationship' => 'Brother', 'contact_phone' => '555']])
            ->assertRedirect(route('portal.show', $employee->portal_token));
        $this->assertNotNull($form->fresh()->completed_at);

        $doc = $employee->tasks()->where('type', 'document')->whereNull('completed_at')->first() ?? $employee->tasks()->where('type', 'document')->first();
        $this->post(route('portal.complete', [$employee->portal_token, $doc]), ['signed_name' => $employee->name, 'signature' => self::SIGNATURE, 'agree' => '1'])
            ->assertRedirect();
        $this->assertSame($employee->name, $doc->fresh()->signed_name);

        $upload = $employee->tasks()->where('type', 'upload')->first();
        if ($upload) {
            $this->post(route('portal.complete', [$employee->portal_token, $upload]), ['file' => UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf')])
                ->assertRedirect();
            $this->assertNotNull($upload->fresh()->file_path);
        }
    }

    public function test_staff_files_require_login(): void
    {
        $this->get(route('reports.export'))->assertRedirect();
    }
}
