<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Department;
use App\Models\EmailTemplate;
use App\Models\Location;
use App\Models\OnboardingTemplate;
use App\Models\Questionnaire;
use App\Models\User;
use App\Models\Workflow;
use App\Models\ZipCode;
use Illuminate\Database\Seeder;

/** Company setup: team, departments, locations, workflows + stages, questionnaires, templates. */
class SetupSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['Lucky Ram', 'admin@hiredesk.test', Role::Admin, 'HR Director'],
            ['Rachel Green', 'rachel@hiredesk.test', Role::Recruiter, 'Senior Recruiter'],
            ['Marcus Hill', 'marcus@hiredesk.test', Role::Recruiter, 'Recruiter'],
            ['Tom Becker', 'tom@hiredesk.test', Role::HiringManager, 'Nurse Manager'],
            ['Dr. Karen White', 'karen@hiredesk.test', Role::HiringManager, 'Chief Nursing Officer'],
            ['Lisa Gomez', 'lisa@hiredesk.test', Role::HiringManager, 'Operations Manager'],
        ];
        foreach ($users as [$name, $email, $role, $title]) {
            User::create(['name' => $name, 'email' => $email, 'password' => 'password', 'role' => $role, 'job_title' => $title])
                ->forceFill(['email_verified_at' => now()])->save();
        }

        foreach ([['Nursing', '#6366f1'], ['Home Care', '#0ea5e9'], ['Therapy', '#10b981'], ['Operations', '#f59e0b'], ['Finance', '#a855f7'], ['IT', '#64748b']] as [$name, $color]) {
            Department::create(['name' => $name, 'color' => $color]);
        }

        $zips = [
            ['02108', 'Boston', 42.3576, -71.0637], ['02115', 'Boston', 42.3428, -71.0927], ['02139', 'Cambridge', 42.3647, -71.1042],
            ['02169', 'Quincy', 42.2490, -71.0021], ['02148', 'Malden', 42.4290, -71.0590], ['02301', 'Brockton', 42.0834, -71.0184],
            ['01601', 'Worcester', 42.2626, -71.8023], ['01605', 'Worcester', 42.2890, -71.7887], ['01103', 'Springfield', 42.1015, -72.5898],
            ['01852', 'Lowell', 42.6334, -71.3162], ['01701', 'Framingham', 42.2793, -71.4162], ['02360', 'Plymouth', 41.9584, -70.6673],
            ['01970', 'Salem', 42.5195, -70.8967], ['02740', 'New Bedford', 41.6362, -70.9342], ['02720', 'Fall River', 41.7015, -71.1550],
            ['01420', 'Fitchburg', 42.5834, -71.8023], ['01040', 'Holyoke', 42.2043, -72.6162], ['02532', 'Bourne', 41.7412, -70.5989],
            ['02451', 'Waltham', 42.3765, -71.2356], ['02472', 'Watertown', 42.3709, -71.1828], ['01810', 'Andover', 42.6583, -71.1370],
            ['01960', 'Peabody', 42.5279, -70.9287], ['02062', 'Norwood', 42.1945, -71.1995], ['01002', 'Amherst', 42.3732, -72.5199],
        ];
        foreach ($zips as [$zip, $city, $lat, $lng]) {
            ZipCode::create(['zip' => $zip, 'city' => $city, 'state' => 'MA', 'latitude' => $lat, 'longitude' => $lng]);
        }

        foreach ([
            ['Boston Medical Campus', 'Boston', '02115', 42.3428, -71.0927, false],
            ['Worcester Care Center', 'Worcester', '01605', 42.2890, -71.7887, false],
            ['Springfield Rehab', 'Springfield', '01103', 42.1015, -72.5898, false],
            ['Cambridge Office', 'Cambridge', '02139', 42.3647, -71.1042, false],
            ['Lowell Home Health', 'Lowell', '01852', 42.6334, -71.3162, false],
            ['Remote', 'Remote', null, null, null, true],
        ] as [$name, $city, $zip, $lat, $lng, $remote]) {
            Location::create(['name' => $name, 'city' => $city, 'state' => 'MA', 'zip' => $zip, 'latitude' => $lat, 'longitude' => $lng, 'is_remote' => $remote]);
        }

        $templates = [
            ['Application received', 'email', 'Thanks for applying, {first_name}!', "Hi {first_name},\n\nThank you for applying for {job_title} at {company}. Our team reviews every application and will be in touch within a few days.\n\nWarm regards,\nThe {company} Talent Team"],
            ['Interview invitation', 'email', 'Interview invitation: {job_title}', "Hi {first_name},\n\nGreat news — we'd like to invite you to interview for {job_title}. You'll receive a calendar invite shortly with the details.\n\nSee you soon,\n{company}"],
            ['Scheduling text', 'sms', null, 'Hi {first_name}, this is {company} about the {job_title} role. What times work for a quick call this week?'],
            ['Reference request', 'email', 'References for {job_title}', "Hi {first_name},\n\nYou're nearly there! Please reply with three professional references.\n\n{company}"],
            ['Offer letter', 'email', 'Your offer from {company}', "Hi {first_name},\n\nWe're excited to offer you the {job_title} position. Please review and e-sign your offer letter using the secure link.\n\n{company}"],
            ['Welcome aboard', 'email', 'Welcome to {company}!', "Hi {first_name},\n\nWelcome to the team! Your new-hire portal is ready — complete your paperwork before day one.\n\n{company} HR"],
            ['Not selected', 'email', 'Your application for {job_title}', "Hi {first_name},\n\nThank you for your interest in {job_title}. After careful review we've decided to move forward with other candidates. We've saved your profile to our talent pool for future roles.\n\nBest wishes,\n{company}"],
        ];
        foreach ($templates as [$name, $channel, $subject, $body]) {
            EmailTemplate::create(compact('name', 'channel', 'subject', 'body'));
        }

        $this->workflows();
        $this->questionnaires();
        $this->onboarding();
    }

    private function workflows(): void
    {
        $tpl = fn (string $name) => EmailTemplate::where('name', $name)->value('id');

        $definitions = [
            ['Nursing', 'Clinical roles with licence checks and panel interviews', true, [
                ['New', '#64748b', 'applied', null, [['type' => 'email', 'template_id' => $tpl('Application received')]]],
                ['Screening', '#0ea5e9', 'review', 3, [['type' => 'auto_reject', 'min_score' => 40], ['type' => 'task', 'note' => 'Review licence and questionnaire']]],
                ['Phone screen', '#38bdf8', 'review', 2, [['type' => 'sms', 'template_id' => $tpl('Scheduling text')], ['type' => 'task', 'note' => 'Book a 15-minute phone screen']]],
                ['Interview', '#6366f1', 'interview', 5, [['type' => 'email', 'template_id' => $tpl('Interview invitation')], ['type' => 'scorecard']]],
                ['Reference', '#a855f7', 'interview', 4, [['type' => 'email', 'template_id' => $tpl('Reference request')], ['type' => 'background_check']]],
                ['Offer', '#f59e0b', 'offer', 3, [['type' => 'offer']]],
                ['Hired', '#10b981', 'hired', null, [['type' => 'email', 'template_id' => $tpl('Welcome aboard')], ['type' => 'onboarding']]],
                ['Not selected', '#ef4444', 'rejected', null, [['type' => 'email', 'template_id' => $tpl('Not selected')], ['type' => 'talent_pool']]],
            ]],
            ['Home care (fast track)', 'High-volume caregiver hiring in five steps', false, [
                ['Applied', '#64748b', 'applied', null, [['type' => 'email', 'template_id' => $tpl('Application received')], ['type' => 'auto_reject', 'min_score' => 50]]],
                ['Phone screen', '#0ea5e9', 'review', 2, [['type' => 'sms', 'template_id' => $tpl('Scheduling text')]]],
                ['Interview', '#6366f1', 'interview', 3, [['type' => 'email', 'template_id' => $tpl('Interview invitation')]]],
                ['Hired', '#10b981', 'hired', null, [['type' => 'onboarding']]],
                ['Not selected', '#ef4444', 'rejected', null, [['type' => 'email', 'template_id' => $tpl('Not selected')]]],
            ]],
            ['Corporate', 'Office and administrative roles', false, [
                ['New', '#64748b', 'applied', null, [['type' => 'email', 'template_id' => $tpl('Application received')]]],
                ['Review', '#0ea5e9', 'review', 5, [['type' => 'task', 'note' => 'Hiring manager resume review']]],
                ['First interview', '#6366f1', 'interview', 5, [['type' => 'email', 'template_id' => $tpl('Interview invitation')], ['type' => 'scorecard']]],
                ['Final interview', '#8b5cf6', 'interview', 5, [['type' => 'scorecard']]],
                ['Offer', '#f59e0b', 'offer', 3, [['type' => 'offer']]],
                ['Hired', '#10b981', 'hired', null, [['type' => 'onboarding']]],
                ['Not selected', '#ef4444', 'rejected', null, [['type' => 'email', 'template_id' => $tpl('Not selected')], ['type' => 'talent_pool']]],
            ]],
            ['Internship', 'Students and new graduates', false, [
                ['Applied', '#64748b', 'applied', null, []],
                ['Interview', '#6366f1', 'interview', 7, []],
                ['Hired', '#10b981', 'hired', null, [['type' => 'onboarding']]],
                ['Not selected', '#ef4444', 'rejected', null, []],
            ]],
        ];

        foreach ($definitions as [$name, $description, $default, $stages]) {
            $workflow = Workflow::create(['name' => $name, 'description' => $description, 'is_default' => $default]);
            foreach ($stages as $i => [$stageName, $color, $category, $sla, $automations]) {
                $workflow->stages()->create([
                    'name' => $stageName, 'color' => $color, 'category' => $category, 'sort' => $i + 1,
                    'sla_days' => $sla, 'notify_team' => $category !== 'rejected', 'automations' => $automations,
                ]);
            }
        }
    }

    private function questionnaires(): void
    {
        Questionnaire::create([
            'name' => 'Registered Nurse screening',
            'description' => 'Licence knock-out plus clinical experience',
            'questions' => [
                ['key' => 'licence', 'label' => 'Do you hold an active Massachusetts RN licence?', 'type' => 'yes_no', 'ideal' => 'yes', 'knockout' => true, 'weight' => 10, 'options' => []],
                ['key' => 'years', 'label' => 'Years of acute-care nursing experience', 'type' => 'number', 'ideal' => '2', 'knockout' => false, 'weight' => 30, 'options' => []],
                ['key' => 'shifts', 'label' => 'Which shifts can you work?', 'type' => 'choice', 'ideal' => 'Any shift', 'knockout' => false, 'weight' => 20, 'options' => ['Days', 'Nights', 'Any shift']],
                ['key' => 'acls', 'label' => 'Are you ACLS and BLS certified?', 'type' => 'yes_no', 'ideal' => 'yes', 'knockout' => false, 'weight' => 20, 'options' => []],
                ['key' => 'why', 'label' => 'Why do you want to join our team?', 'type' => 'text', 'ideal' => null, 'knockout' => false, 'weight' => 0, 'options' => []],
            ],
            'keywords' => ['ICU', 'ACLS', 'BLS', 'Epic', 'Ventilator', 'Telemetry'],
            'keyword_points' => 20,
        ]);

        Questionnaire::create([
            'name' => 'Caregiver screening',
            'description' => 'Home health aide and CNA roles',
            'questions' => [
                ['key' => 'age', 'label' => 'Are you 18 or older?', 'type' => 'yes_no', 'ideal' => 'yes', 'knockout' => true, 'weight' => 10, 'options' => []],
                ['key' => 'car', 'label' => 'Do you have a valid driver\'s licence and reliable transportation?', 'type' => 'yes_no', 'ideal' => 'yes', 'knockout' => true, 'weight' => 20, 'options' => []],
                ['key' => 'cert', 'label' => 'Which certification do you hold?', 'type' => 'choice', 'ideal' => 'HHA', 'knockout' => false, 'weight' => 30, 'options' => ['HHA', 'CNA', 'None yet']],
                ['key' => 'weekends', 'label' => 'Can you work weekends?', 'type' => 'yes_no', 'ideal' => 'yes', 'knockout' => false, 'weight' => 20, 'options' => []],
            ],
            'keywords' => ['Home health', 'HHA', 'CNA', 'Dementia care', 'CPR'],
            'keyword_points' => 20,
        ]);

        Questionnaire::create([
            'name' => 'General office screening',
            'description' => 'Corporate and administrative roles',
            'questions' => [
                ['key' => 'authorized', 'label' => 'Are you legally authorized to work in the US?', 'type' => 'yes_no', 'ideal' => 'yes', 'knockout' => true, 'weight' => 10, 'options' => []],
                ['key' => 'years', 'label' => 'Years of relevant experience', 'type' => 'number', 'ideal' => '2', 'knockout' => false, 'weight' => 40, 'options' => []],
                ['key' => 'onsite', 'label' => 'Are you able to work on-site three days a week?', 'type' => 'yes_no', 'ideal' => 'yes', 'knockout' => false, 'weight' => 30, 'options' => []],
            ],
            'keywords' => ['Excel', 'Customer service', 'Medical billing', 'Microsoft Office', 'Scheduling'],
            'keyword_points' => 20,
        ]);
    }

    private function onboarding(): void
    {
        $personal = [
            ['name' => 'preferred_name', 'label' => 'Preferred name', 'type' => 'text', 'required' => false],
            ['name' => 'address', 'label' => 'Home address', 'type' => 'textarea', 'required' => true],
            ['name' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date', 'required' => true],
            ['name' => 'shirt_size', 'label' => 'Uniform size', 'type' => 'select', 'required' => false, 'options' => 'XS,S,M,L,XL,XXL'],
        ];
        $emergency = [
            ['name' => 'contact_name', 'label' => 'Contact name', 'type' => 'text', 'required' => true],
            ['name' => 'relationship', 'label' => 'Relationship', 'type' => 'text', 'required' => true],
            ['name' => 'contact_phone', 'label' => 'Phone', 'type' => 'tel', 'required' => true],
        ];
        $deposit = [
            ['name' => 'bank_name', 'label' => 'Bank name', 'type' => 'text', 'required' => true],
            ['name' => 'account_type', 'label' => 'Account type', 'type' => 'select', 'required' => true, 'options' => 'Checking,Savings'],
            ['name' => 'routing_last4', 'label' => 'Routing number (last 4 digits only in demo)', 'type' => 'text', 'required' => true],
        ];
        $tax = [
            ['name' => 'filing_status', 'label' => 'Filing status', 'type' => 'select', 'required' => true, 'options' => 'Single,Married filing jointly,Head of household'],
            ['name' => 'dependents', 'label' => 'Number of dependents', 'type' => 'number', 'required' => false],
        ];

        $tasks = [
            ['title' => 'Personal information', 'type' => 'form', 'assignee' => 'new_hire', 'due_offset_days' => -10, 'fields' => $personal, 'description' => 'Tell us how to reach you and what to call you.'],
            ['title' => 'Emergency contacts', 'type' => 'form', 'assignee' => 'new_hire', 'due_offset_days' => -10, 'fields' => $emergency, 'description' => 'Who should we call in an emergency?'],
            ['title' => 'Direct deposit', 'type' => 'form', 'assignee' => 'new_hire', 'due_offset_days' => -7, 'fields' => $deposit, 'description' => 'Where should we send your pay?'],
            ['title' => 'Tax withholding', 'type' => 'form', 'assignee' => 'new_hire', 'due_offset_days' => -7, 'fields' => $tax, 'description' => 'Federal and state withholding elections.'],
            ['title' => 'Employee handbook acknowledgment', 'type' => 'document', 'assignee' => 'new_hire', 'due_offset_days' => -5, 'description' => 'Read and e-sign the employee handbook.', 'document' => "I acknowledge that I have received and read the Employee Handbook (v2026.2). I understand the policies on attendance, conduct, patient privacy (HIPAA), safety and anti-harassment, and I agree to follow them."],
            ['title' => 'Confidentiality & HIPAA agreement', 'type' => 'document', 'assignee' => 'new_hire', 'due_offset_days' => -5, 'description' => 'E-sign the confidentiality agreement.', 'document' => "I agree to keep all patient, employee and company information confidential and to access protected health information only as needed for my job duties, in line with HIPAA."],
            ['title' => 'Licence & certification upload', 'type' => 'upload', 'assignee' => 'new_hire', 'due_offset_days' => -3, 'description' => 'Upload a photo or PDF of your professional licence and CPR card.'],
            ['title' => 'HIPAA training video', 'type' => 'training', 'assignee' => 'new_hire', 'due_offset_days' => 3, 'description' => '25-minute online course. Mark complete when finished.'],
            ['title' => 'Set up email and EHR access', 'type' => 'task', 'assignee' => 'it', 'due_offset_days' => -2, 'description' => 'Create accounts, assign laptop and badge.'],
            ['title' => 'Verify employment eligibility documents', 'type' => 'task', 'assignee' => 'hr', 'due_offset_days' => 1, 'description' => 'Review identity and work-authorization documents on day one.'],
            ['title' => 'First-week plan and buddy', 'type' => 'task', 'assignee' => 'manager', 'due_offset_days' => 0, 'description' => 'Share the first-week schedule and introduce a peer buddy.'],
            ['title' => 'Benefits enrollment', 'type' => 'task', 'assignee' => 'new_hire', 'due_offset_days' => 30, 'description' => 'Choose medical, dental and vision plans within 30 days of starting.'],
        ];

        OnboardingTemplate::create(['name' => 'Clinical staff', 'description' => 'Nurses, therapists and caregivers', 'tasks' => $tasks, 'is_default' => true]);

        OnboardingTemplate::create([
            'name' => 'Office staff',
            'description' => 'Corporate and remote roles',
            'tasks' => collect($tasks)->reject(fn ($t) => in_array($t['title'], ['Licence & certification upload', 'Confidentiality & HIPAA agreement'], true))->values()->all(),
        ]);
    }
}
