<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HireDesk schema: recruiting (jobs, workflows, stages, candidates, applications),
 * collaboration (interviews, scorecards, notes, messages, offers) and onboarding.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('recruiter')->after('email');
            $table->string('job_title')->nullable()->after('role');
            $table->string('phone')->nullable()->after('job_title');
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->default('#6366f1');
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
            $table->string('state', 2);
            $table->string('zip', 10)->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->boolean('is_remote')->default(false);
            $table->timestamps();
        });

        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 9)->default('#64748b');
            $table->string('category');
            $table->unsignedInteger('sort')->default(0);
            $table->unsignedInteger('sla_days')->nullable();
            $table->boolean('notify_team')->default(true);
            $table->json('automations')->nullable();
            $table->timestamps();
        });

        Schema::create('questionnaires', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('questions')->nullable();
            $table->json('keywords')->nullable();
            $table->unsignedInteger('keyword_points')->default(0);
            $table->timestamps();
        });

        Schema::create('job_openings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('reference')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workflow_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('questionnaire_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('hiring_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employment_type')->default('full_time');
            $table->string('workplace')->default('on_site');
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->string('salary_period')->default('year');
            $table->unsignedInteger('openings')->default(1);
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('status')->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->date('closes_at')->nullable();
            $table->timestamps();
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip', 10)->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('current_title')->nullable();
            $table->unsignedInteger('years_experience')->nullable();
            $table->string('source')->nullable();
            $table->string('resume_path')->nullable();
            $table->longText('resume_text')->nullable();
            $table->json('skills')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('in_talent_pool')->default(false);
            $table->boolean('sms_opt_in')->default(true);
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_opening_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->boolean('knocked_out')->default(false);
            $table->unsignedTinyInteger('rating')->nullable();
            $table->json('answers')->nullable();
            $table->json('eeo')->nullable();
            $table->text('cover_letter')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('stage_entered_at')->nullable();
            $table->timestamp('hired_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->unique(['candidate_id', 'job_opening_id']);
        });

        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('video');
            $table->dateTime('scheduled_at');
            $table->unsignedInteger('duration_minutes')->default(45);
            $table->string('location')->nullable();
            $table->json('interviewer_ids')->nullable();
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('scorecards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interview_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('round')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->string('recommendation');
            $table->text('strengths')->nullable();
            $table->text('concerns')->nullable();
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('channel')->default('email');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->default('email');
            $table->string('direction')->default('outbound');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('automated')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('salary', 10, 2);
            $table->string('salary_period')->default('year');
            $table->date('start_date');
            $table->date('expires_at')->nullable();
            $table->longText('body')->nullable();
            $table->string('status')->default('draft');
            $table->string('token')->unique();
            $table->string('signed_name')->nullable();
            $table->longText('signature')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('onboarding_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('tasks')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('onboarding_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('job_title');
            $table->string('manager_name')->nullable();
            $table->date('start_date');
            $table->string('status')->default('onboarding');
            $table->string('portal_token')->unique();
            $table->json('profile')->nullable();
            $table->timestamps();
        });

        Schema::create('onboarding_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('task');
            $table->string('assignee')->default('new_hire');
            $table->unsignedInteger('sort')->default(0);
            $table->date('due_date')->nullable();
            $table->json('data')->nullable();
            $table->string('signed_name')->nullable();
            $table->longText('signature')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('note');
            $table->string('description');
            $table->timestamps();
        });

        Schema::create('zip_codes', function (Blueprint $table) {
            $table->string('zip', 10)->primary();
            $table->string('city');
            $table->string('state', 2);
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
        });
    }

    public function down(): void
    {
        foreach (['zip_codes', 'activities', 'onboarding_tasks', 'employees', 'onboarding_templates', 'offers', 'messages',
            'email_templates', 'notes', 'scorecards', 'interviews', 'applications', 'candidates', 'job_openings',
            'questionnaires', 'stages', 'workflows', 'locations', 'departments'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['role', 'job_title', 'phone']));
    }
};
