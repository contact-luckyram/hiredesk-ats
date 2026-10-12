<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use App\Enums\JobStatus;
use App\Enums\MessageChannel;
use App\Enums\OfferStatus;
use App\Enums\Recommendation;
use App\Enums\StageCategory;
use App\Enums\Workplace;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Interview;
use App\Models\JobOpening;
use App\Models\Location;
use App\Models\Message;
use App\Models\Note;
use App\Models\Offer;
use App\Models\OnboardingTemplate;
use App\Models\Questionnaire;
use App\Models\Scorecard;
use App\Models\Stage;
use App\Models\User;
use App\Models\Workflow;
use App\Services\OnboardingService;
use App\Services\ScreeningService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Realistic, repeatable demo data: 12 jobs, ~170 candidates spread over ten months,
 * interviews this week, scorecards, offers and three new hires in onboarding.
 */
class RecruitingSeeder extends Seeder
{
    private const FIRST = ['Olivia', 'Liam', 'Emma', 'Noah', 'Ava', 'Ethan', 'Sophia', 'Mason', 'Isabella', 'Lucas', 'Mia', 'Logan', 'Amelia', 'James', 'Harper', 'Benjamin', 'Evelyn', 'Elijah', 'Abigail', 'Henry', 'Emily', 'Alexander', 'Ella', 'Sebastian', 'Grace', 'Jack', 'Chloe', 'Owen', 'Victoria', 'Samuel', 'Riley', 'Daniel', 'Aria', 'Matthew', 'Lily', 'Joseph', 'Zoey', 'David', 'Nora', 'Carter', 'Hannah', 'Wyatt', 'Layla', 'Jayden', 'Priya', 'Arjun', 'Mei', 'Kenji', 'Fatima', 'Omar', 'Lucia', 'Mateo', 'Aaliyah', 'Malik', 'Nia', 'Andre', 'Rosa', 'Diego', 'Ana', 'Thanh'];

    private const LAST = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Thompson', 'White', 'Harris', 'Clark', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Nguyen', 'Hill', 'Flores', 'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts', 'Patel', 'Shah', 'Kim', 'Chen', 'O\'Brien', 'Sullivan', 'Murphy', 'Okafor', 'Haddad', 'Silva'];

    private const SOURCES = ['Indeed' => 34, 'Careers page' => 24, 'Referral' => 14, 'LinkedIn' => 12, 'ZipRecruiter' => 9, 'Glassdoor' => 4, 'Job fair' => 3];

    private const CITIES = [['Boston', '02115'], ['Quincy', '02169'], ['Cambridge', '02139'], ['Worcester', '01605'], ['Springfield', '01103'], ['Lowell', '01852'], ['Brockton', '02301'], ['Framingham', '01701'], ['Malden', '02148'], ['Salem', '01970'], ['Waltham', '02451'], ['Holyoke', '01040']];

    private Collection $users;

    public function run(): void
    {
        mt_srand(2026);
        $this->users = User::all()->keyBy('email');

        $jobs = $this->jobs();
        $this->featured($jobs);
        $this->bulk($jobs);
        $this->interviewsThisWeek();
    }

    private function jobs(): Collection
    {
        $d = Department::pluck('id', 'name');
        $l = Location::pluck('id', 'city');
        $w = Workflow::pluck('id', 'name');
        $q = Questionnaire::pluck('id', 'name');
        $u = fn ($e) => $this->users[$e]->id;

        $rows = [
            ['Registered Nurse – ICU', 'Nursing', 'Boston', 'Nursing', 'Registered Nurse screening', 'tom', EmploymentType::FullTime, Workplace::OnSite, 42, 55, 'hour', 3, JobStatus::Published, true, 'Provide critical care to adult ICU patients in a 24-bed unit with 1:2 ratios and a supportive team.'],
            ['Charge Nurse – Med/Surg', 'Nursing', 'Worcester', 'Nursing', 'Registered Nurse screening', 'karen', EmploymentType::FullTime, Workplace::OnSite, 45, 58, 'hour', 1, JobStatus::Published, false, 'Lead a 32-bed medical-surgical unit, coordinate patient flow and mentor new graduate nurses.'],
            ['Hospice RN Case Manager', 'Nursing', 'Springfield', 'Nursing', 'Registered Nurse screening', 'karen', EmploymentType::FullTime, Workplace::Hybrid, 40, 52, 'hour', 2, JobStatus::Published, false, 'Manage a caseload of hospice patients at home, partnering with families and the interdisciplinary team.'],
            ['LPN – Home Health', 'Nursing', 'Lowell', 'Nursing', 'Registered Nurse screening', 'tom', EmploymentType::PerDiem, Workplace::OnSite, 30, 36, 'hour', 2, JobStatus::Published, false, 'Deliver skilled nursing visits to patients at home across the Merrimack Valley.'],
            ['Home Health Aide', 'Home Care', 'Worcester', 'Home care (fast track)', 'Caregiver screening', 'lisa', EmploymentType::PartTime, Workplace::OnSite, 19, 23, 'hour', 8, JobStatus::Published, true, 'Help clients with daily living, companionship and light housekeeping. Flexible schedules, mileage paid.'],
            ['Certified Nursing Assistant', 'Home Care', 'Boston', 'Home care (fast track)', 'Caregiver screening', 'lisa', EmploymentType::FullTime, Workplace::OnSite, 21, 26, 'hour', 5, JobStatus::Published, false, 'Support nurses with patient care, vitals and mobility on a busy step-down unit.'],
            ['Physical Therapist', 'Therapy', 'Springfield', 'Nursing', null, 'karen', EmploymentType::FullTime, Workplace::OnSite, 85000, 105000, 'year', 1, JobStatus::Published, true, 'Evaluate and treat outpatient orthopedic and neurological patients in our new rehab gym.'],
            ['Occupational Therapist', 'Therapy', 'Boston', 'Nursing', null, 'karen', EmploymentType::PartTime, Workplace::OnSite, 80000, 98000, 'year', 1, JobStatus::Published, false, 'Help patients regain independence after injury or illness in inpatient and home settings.'],
            ['Front Desk Coordinator', 'Operations', 'Remote', 'Corporate', 'General office screening', 'lisa', EmploymentType::FullTime, Workplace::Remote, 42000, 50000, 'year', 2, JobStatus::Published, false, 'Be the friendly first voice of our clinics: scheduling, insurance verification and patient questions.'],
            ['Billing Specialist', 'Finance', 'Cambridge', 'Corporate', 'General office screening', 'lisa', EmploymentType::FullTime, Workplace::Hybrid, 52000, 62000, 'year', 1, JobStatus::PendingApproval, false, 'Submit and follow up on Medicare and commercial claims, resolve denials and post payments.'],
            ['IT Support Analyst', 'IT', 'Boston', 'Corporate', 'General office screening', 'lisa', EmploymentType::FullTime, Workplace::Hybrid, 58000, 72000, 'year', 1, JobStatus::Draft, false, 'Keep clinicians productive: help desk, laptops, EHR access and network troubleshooting.'],
            ['Nursing Student Intern – Summer 2027', 'Nursing', 'Boston', 'Internship', null, 'tom', EmploymentType::Internship, Workplace::OnSite, 20, 22, 'hour', 6, JobStatus::Published, false, 'Ten-week paid summer internship for BSN students, rotating through ICU, Med/Surg and the ED.'],
        ];

        $jobs = collect();
        foreach ($rows as $i => [$title, $dept, $city, $workflow, $questionnaire, $manager, $type, $place, $min, $max, $period, $openings, $status, $featured, $summary]) {
            $jobs->push(JobOpening::create([
                'title' => $title,
                'department_id' => $d[$dept],
                'location_id' => $place === Workplace::Remote ? $l['Remote'] : $l[$city],
                'workflow_id' => $w[$workflow],
                'questionnaire_id' => $questionnaire ? $q[$questionnaire] : null,
                'hiring_manager_id' => $u("{$manager}@hiredesk.test"),
                'recruiter_id' => $u($i % 2 ? 'marcus@hiredesk.test' : 'rachel@hiredesk.test'),
                'employment_type' => $type,
                'workplace' => $place,
                'salary_min' => $min,
                'salary_max' => $max,
                'salary_period' => $period,
                'openings' => $openings,
                'summary' => $summary,
                'description' => $this->description($title, $summary),
                'status' => $status,
                'is_featured' => $featured,
                'published_at' => $status === JobStatus::Published ? now()->subDays(mt_rand(3, 60)) : null,
                'closes_at' => now()->addDays(mt_rand(20, 75)),
            ]));
        }

        return $jobs;
    }

    private function description(string $title, string $summary): string
    {
        $company = config('hiredesk.company');

        return "<p>{$summary}</p>"
            ."<h3>What you'll do</h3><ul><li>Deliver safe, compassionate, evidence-based care</li><li>Document accurately in Epic and communicate with the care team</li><li>Educate patients and families</li><li>Take part in quality and safety initiatives</li></ul>"
            ."<h3>What you'll bring</h3><ul><li>Relevant licence or certification in good standing</li><li>BLS (ACLS where required)</li><li>Strong communication and teamwork</li><li>Bilingual English/Spanish a plus</li></ul>"
            ."<h3>Why {$company}</h3><ul><li>Medical, dental and vision from day one</li><li>401(k) with 5% match</li><li>Tuition reimbursement up to \$5,250 a year</li><li>Paid time off that grows with you</li></ul>";
    }

    /** Named candidates used in the client demo walkthrough. */
    private function featured(Collection $jobs): void
    {
        $rn = $jobs->firstWhere('title', 'Registered Nurse – ICU');
        $pt = $jobs->firstWhere('title', 'Physical Therapist');
        $billing = $jobs->firstWhere('title', 'Billing Specialist');
        $hha = $jobs->firstWhere('title', 'Home Health Aide');
        $front = $jobs->firstWhere('title', 'Front Desk Coordinator');

        $maria = $this->application($rn, 'Maria', 'Lopez', 'Offer', 'Indeed', 22, ['city' => 'Quincy', 'zip' => '02169', 'years' => 7, 'title' => 'ICU Staff Nurse', 'skills' => ['ACLS', 'BLS', 'Epic', 'ICU', 'Ventilator', 'Telemetry'], 'rating' => 5]);
        $this->scorecards($maria, [['karen@hiredesk.test', 'Clinical panel', 5, Recommendation::StrongHire], ['tom@hiredesk.test', 'Hiring manager', 5, Recommendation::StrongHire], ['lisa@hiredesk.test', 'Culture fit', 4, Recommendation::Hire]]);
        $offer = $this->offer($maria, 52 * 2080, OfferStatus::Sent);
        Activity::create(['application_id' => $maria->id, 'candidate_id' => $maria->candidate_id, 'user_id' => $this->users['karen@hiredesk.test']->id, 'type' => 'offer', 'description' => 'Offer approved by Dr. Karen White', 'created_at' => now()->subHours(3)]);
        Message::create(['candidate_id' => $maria->candidate_id, 'application_id' => $maria->id, 'channel' => MessageChannel::Sms, 'direction' => 'inbound', 'body' => 'Thanks! Thursday 10am works for me.', 'sent_at' => now()->subDays(10)]);
        Note::create(['application_id' => $maria->id, 'user_id' => $this->users['tom@hiredesk.test']->id, 'body' => 'Excellent clinical judgement in the case scenario. Team loved her. Push to offer quickly — she has a competing offer.']);

        $this->application($rn, 'James', 'Carter', 'Screening', 'Careers page', 3, ['city' => 'Boston', 'zip' => '02115', 'years' => 4, 'skills' => ['BLS', 'Epic', 'Telemetry'], 'rating' => 3]);
        $priya = $this->application($rn, 'Priya', 'Shah', 'Interview', 'Referral', 12, ['city' => 'Cambridge', 'zip' => '02139', 'years' => 5, 'skills' => ['ACLS', 'BLS', 'ICU', 'Epic'], 'rating' => 4]);
        $this->application($rn, 'Henry', 'Young', 'Reference', 'LinkedIn', 16, ['city' => 'Malden', 'zip' => '02148', 'years' => 9, 'skills' => ['ACLS', 'ICU', 'Ventilator'], 'rating' => 4]);
        $this->application($rn, 'Ethan', 'Brooks', 'New', 'Indeed', 0, ['city' => 'Brockton', 'zip' => '02301', 'years' => 3, 'skills' => ['BLS', 'Telemetry'], 'rating' => null]);
        $this->application($rn, 'Sofia', 'Nguyen', 'New', 'Careers page', 0, ['city' => 'Quincy', 'zip' => '02169', 'years' => 2, 'skills' => ['BLS'], 'rating' => null]);
        $this->application($rn, 'Olivia', 'Reed', 'Phone screen', 'Indeed', 6, ['city' => 'Waltham', 'zip' => '02451', 'years' => 6, 'skills' => ['ACLS', 'BLS', 'Epic'], 'rating' => 3]);

        $aisha = $this->application($pt, 'Aisha', 'Bello', 'Hired', 'Referral', 30, ['city' => 'Springfield', 'zip' => '01103', 'years' => 6, 'title' => 'Physical Therapist', 'skills' => ['Physical therapy', 'Geriatrics', 'CPR'], 'rating' => 5]);
        $daniel = $this->application($billing, 'Daniel', 'Kim', 'Hired', 'LinkedIn', 26, ['city' => 'Cambridge', 'zip' => '02139', 'years' => 5, 'title' => 'Billing Specialist', 'skills' => ['Medical billing', 'ICD-10', 'CPT', 'Excel'], 'rating' => 5]);
        $grace = $this->application($hha, 'Grace', 'Wilson', 'Hired', 'Job fair', 14, ['city' => 'Worcester', 'zip' => '01605', 'years' => 3, 'title' => 'Home Health Aide', 'skills' => ['HHA', 'CPR', 'Dementia care'], 'rating' => 4]);
        $this->application($front, 'Lucia', 'Silva', 'First interview', 'Indeed', 8, ['city' => 'Framingham', 'zip' => '01701', 'years' => 4, 'skills' => ['Customer service', 'Scheduling', 'Spanish', 'Bilingual'], 'rating' => 4]);

        $onboarding = app(OnboardingService::class);
        $clinical = OnboardingTemplate::where('name', 'Clinical staff')->first();
        $office = OnboardingTemplate::where('name', 'Office staff')->first();

        foreach ([[$aisha, $clinical, 13, 6, 96000], [$daniel, $office, 6, 4, 58000], [$grace, $clinical, 20, 2, 22]] as [$app, $template, $startsIn, $done, $pay]) {
            $this->offer($app, $pay, OfferStatus::Signed);
            $employee = $onboarding->startFromApplication($app->fresh(['candidate', 'jobOpening', 'offer']), $template, now()->addDays($startsIn)->startOfDay());
            $employee->tasks()->where('assignee', 'new_hire')->orderBy('sort')->take($done)->get()->each(function ($task) use ($employee) {
                $task->update([
                    'completed_at' => now()->subDays(mt_rand(0, 3)),
                    'signed_name' => $task->type->value === 'document' ? $employee->name : null,
                    'data' => array_merge($task->data ?? [], ['values' => ['contact_name' => 'Sam '.$employee->last_name, 'relationship' => 'Sibling', 'contact_phone' => '(617) 555-0199', 'address' => '12 Elm St, Springfield, MA', 'date_of_birth' => '1992-04-18', 'bank_name' => 'Eastern Bank', 'account_type' => 'Checking', 'routing_last4' => '4821', 'filing_status' => 'Single']]),
                ]);
            });
        }

        // A handful of employees who already finished onboarding (for reports).
        foreach ([['Noah', 'Davis', 'Registered Nurse – ICU', 'Nursing'], ['Mia', 'Clark', 'Certified Nursing Assistant', 'Home Care'], ['Owen', 'Murphy', 'Front Desk Coordinator', 'Operations']] as $i => [$first, $last, $title, $dept]) {
            $job = $jobs->firstWhere('title', $title);
            $app = $this->application($job, $first, $last, 'Hired', 'Indeed', 90 + $i * 30, ['rating' => 4]);
            $employee = $onboarding->startFromApplication($app->fresh(['candidate', 'jobOpening', 'offer']), $clinical, now()->subDays(40 + $i * 30));
            $employee->tasks()->update(['completed_at' => now()->subDays(45 + $i * 30)]);
            $employee->update(['status' => 'active']);
        }

        $this->interview($priya, 'Panel interview', InterviewType::Panel, now()->next('Thursday')->setTime(10, 0), ['karen@hiredesk.test', 'tom@hiredesk.test']);
    }

    private function bulk(Collection $jobs): void
    {
        $weights = [
            'applied' => 22, 'review' => 18, 'interview' => 10, 'offer' => 2, 'hired' => 9, 'rejected' => 39,
        ];

        foreach ($jobs as $job) {
            if ($job->status === JobStatus::Draft) {
                continue;
            }
            $stages = $job->workflow->stages->groupBy(fn ($s) => $s->category->value);
            $count = match (true) {
                $job->is_featured => mt_rand(20, 26),
                $job->status === JobStatus::PendingApproval => 4,
                default => mt_rand(9, 15),
            };

            for ($i = 0; $i < $count; $i++) {
                $category = $this->weighted($weights);
                $stage = ($stages[$category] ?? $stages['applied'])->random();
                $daysAgo = in_array($category, ['applied', 'review'], true) ? mt_rand(0, 14) : mt_rand(5, 290);
                $this->application($job, $this->pick(self::FIRST), $this->pick(self::LAST), $stage->name, $this->weighted(self::SOURCES), $daysAgo, []);
            }
        }
    }

    private function application(JobOpening $job, string $first, string $last, string $stageName, string $source, int $daysAgo, array $opt): Application
    {
        [$city, $zip] = isset($opt['city']) ? [$opt['city'], $opt['zip']] : self::CITIES[array_rand(self::CITIES)];
        $email = strtolower(preg_replace('/[^a-z]/i', '', $first).'.'.preg_replace('/[^a-z]/i', '', $last));
        $n = 1;
        while (Candidate::where('email', $email.($n > 1 ? $n : '').'@example.com')->exists()) {
            $n++;
        }
        $years = $opt['years'] ?? mt_rand(0, 15);
        $skills = $opt['skills'] ?? collect(config('hiredesk.skills'))->shuffle()->take(mt_rand(2, 5))->values()->all();

        $candidate = Candidate::create([
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email.($n > 1 ? $n : '').'@example.com',
            'phone' => sprintf('(%d) 555-%04d', [617, 508, 413, 978, 781][mt_rand(0, 4)], mt_rand(100, 9999)),
            'city' => $city,
            'state' => 'MA',
            'zip' => $zip,
            'current_title' => $opt['title'] ?? null,
            'years_experience' => $years,
            'source' => $source,
            'skills' => $skills,
            'resume_text' => "{$first} {$last}\n{$city}, MA\n{$years} years of experience. Skills: ".implode(', ', $skills),
            'tags' => mt_rand(0, 6) === 0 ? ['Bilingual'] : [],
        ]);

        $stage = $job->workflow->stages->firstWhere('name', $stageName) ?? $job->workflow->stages->first();
        $category = $stage->category;
        $appliedAt = now()->subDays($daysAgo)->subHours(mt_rand(0, 20));
        $answers = $this->answers($job->questionnaire, $years, $category);
        $result = app(ScreeningService::class)->score($job->questionnaire, $answers, $candidate->resume_text);
        $score = $result['score'];
        if ($score !== null && isset($opt['rating']) && $opt['rating'] >= 5) {
            $score = max($score, 95);
        }

        $stageEntered = match ($category) {
            StageCategory::Applied => $appliedAt,
            StageCategory::Review => $appliedAt->clone()->addDays(min($daysAgo, mt_rand(0, 2))),
            default => $appliedAt->clone()->addDays(min($daysAgo, mt_rand(6, 20))),
        };
        $hiredAt = $category === StageCategory::Hired ? $appliedAt->clone()->addDays(min(max($daysAgo, 1), mt_rand(12, 34))) : null;

        $application = Application::create([
            'candidate_id' => $candidate->id,
            'job_opening_id' => $job->id,
            'stage_id' => $stage->id,
            'source' => $source,
            'score' => $score,
            'knocked_out' => $result['knocked_out'] && $category !== StageCategory::Hired,
            'rating' => $opt['rating'] ?? ($category === StageCategory::Applied ? null : mt_rand(2, 5)),
            'answers' => ['values' => $answers, 'breakdown' => $result['breakdown']],
            'eeo' => $this->eeo(),
            'position' => mt_rand(1, 100),
            'applied_at' => $appliedAt,
            'stage_entered_at' => $hiredAt ?? $stageEntered,
            'hired_at' => $hiredAt,
            'rejected_at' => $category === StageCategory::Rejected ? $stageEntered : null,
            'created_at' => $appliedAt,
        ]);

        Activity::create(['application_id' => $application->id, 'candidate_id' => $candidate->id, 'type' => 'applied', 'description' => "Applied via {$source}".($score !== null ? " · screening score {$score}" : ''), 'created_at' => $appliedAt]);
        Message::create(['candidate_id' => $candidate->id, 'application_id' => $application->id, 'channel' => MessageChannel::Email, 'subject' => "Thanks for applying, {$first}!", 'body' => "Hi {$first},\n\nThank you for applying for {$job->title}.", 'automated' => true, 'sent_at' => $appliedAt->clone()->addMinute(), 'created_at' => $appliedAt]);
        if ($category !== StageCategory::Applied) {
            Activity::create(['application_id' => $application->id, 'candidate_id' => $candidate->id, 'type' => 'stage', 'description' => "Moved to {$stage->name}", 'created_at' => $hiredAt ?? $stageEntered]);
        }

        return $application->load(['candidate', 'jobOpening', 'stage']);
    }

    private function answers(?Questionnaire $q, int $years, StageCategory $category): array
    {
        if (! $q) {
            return [];
        }
        $good = $category !== StageCategory::Rejected || mt_rand(0, 1);

        return collect($q->questions)->mapWithKeys(function ($question) use ($good, $years) {
            $value = match ($question['type']) {
                'number' => (string) $years,
                'choice' => $good ? $question['ideal'] : $question['options'][array_rand($question['options'])],
                'text' => 'I love patient care and your team has a great reputation.',
                default => ($good || ! $question['knockout']) && mt_rand(0, 8) ? 'yes' : 'no',
            };

            return [$question['key'] => $value];
        })->all();
    }

    private function eeo(): array
    {
        if (mt_rand(0, 3) === 0) {
            return [];
        }

        return [
            'gender' => $this->weighted(['Female' => 70, 'Male' => 26, 'Decline to answer' => 4]),
            'race' => $this->weighted(['White' => 52, 'Black or African American' => 16, 'Hispanic or Latino' => 15, 'Asian' => 11, 'Two or more races' => 3, 'Decline to answer' => 3]),
            'veteran' => $this->weighted(['Not a protected veteran' => 92, 'Protected veteran' => 5, 'Decline to answer' => 3]),
        ];
    }

    private function scorecards(Application $app, array $rows): void
    {
        foreach ($rows as [$email, $round, $rating, $rec]) {
            Scorecard::create([
                'application_id' => $app->id, 'user_id' => $this->users[$email]->id, 'round' => $round, 'rating' => $rating,
                'recommendation' => $rec, 'strengths' => 'Calm under pressure, strong assessment skills, great rapport.', 'concerns' => $rating < 5 ? 'Limited charge-nurse experience.' : null,
            ]);
        }
    }

    private function offer(Application $app, float $salary, OfferStatus $status): Offer
    {
        $period = $app->jobOpening->salary_period;
        $salary = $period === 'hour' ? round($salary / ($salary > 1000 ? 2080 : 1), 2) : $salary;
        $start = now()->addDays(14)->toDateString();

        return Offer::create([
            'application_id' => $app->id,
            'approved_by' => $this->users['karen@hiredesk.test']->id,
            'salary' => $salary,
            'salary_period' => $period,
            'start_date' => $start,
            'expires_at' => now()->addDays(5),
            'status' => $status,
            'body' => Offer::defaultBody($app, $salary, $period, $start),
            'sent_at' => now()->subHours(2),
            'signed_name' => $status === OfferStatus::Signed ? $app->candidate->name : null,
            'signed_at' => $status === OfferStatus::Signed ? now()->subDays(3) : null,
        ]);
    }

    private function interview(Application $app, string $title, InterviewType $type, Carbon $at, array $emails, InterviewStatus $status = InterviewStatus::Scheduled): Interview
    {
        return Interview::create([
            'application_id' => $app->id,
            'title' => $title,
            'type' => $type,
            'scheduled_at' => $at,
            'duration_minutes' => $type === InterviewType::Phone ? 20 : 45,
            'location' => match ($type) {
                InterviewType::Video => 'Video call (link in invite)',
                InterviewType::Phone => 'Phone',
                default => $app->jobOpening->location?->name,
            },
            'interviewer_ids' => collect($emails)->map(fn ($e) => $this->users[$e]->id)->all(),
            'status' => $status,
        ]);
    }

    private function interviewsThisWeek(): void
    {
        $apps = Application::with(['stage', 'jobOpening.location', 'candidate'])
            ->whereHas('stage', fn ($q) => $q->whereIn('category', ['interview', 'review']))
            ->whereDoesntHave('interviews')
            ->get()->shuffle()->take(16);

        $emails = ['tom@hiredesk.test', 'karen@hiredesk.test', 'lisa@hiredesk.test', 'rachel@hiredesk.test', 'marcus@hiredesk.test'];
        foreach ($apps as $i => $app) {
            $type = $app->stage->category === StageCategory::Review ? InterviewType::Phone : [InterviewType::Video, InterviewType::OnSite, InterviewType::Panel][$i % 3];
            $at = now()->startOfDay()->addDays(intdiv($i, 3) - 1)->setTime([9, 11, 14, 15][$i % 4], $i % 2 ? 30 : 0);
            $status = $at->isPast() ? InterviewStatus::Completed : InterviewStatus::Scheduled;
            $interview = $this->interview($app, $type === InterviewType::Phone ? 'Phone screen' : $type->getLabel().' interview', $type, $at, [$emails[$i % 5], $emails[($i + 1) % 5]], $status);
            if ($status === InterviewStatus::Completed && $type !== InterviewType::Phone) {
                Scorecard::create(['application_id' => $app->id, 'interview_id' => $interview->id, 'user_id' => $this->users[$emails[$i % 5]]->id, 'round' => $interview->title, 'rating' => mt_rand(3, 5), 'recommendation' => [Recommendation::Hire, Recommendation::StrongHire, Recommendation::NoHire][$i % 3]]);
            }
        }
    }

    private function pick(array $list): string
    {
        return $list[mt_rand(0, count($list) - 1)];
    }

    private function weighted(array $weights): string
    {
        $r = mt_rand(1, array_sum($weights));
        foreach ($weights as $key => $w) {
            if (($r -= $w) <= 0) {
                return (string) $key;
            }
        }

        return (string) array_key_first($weights);
    }
}
