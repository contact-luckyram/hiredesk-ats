<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobOpening;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Handles a new application from the careers site or a recruiter: de-duplicates the
 * candidate by email, parses the resume, scores the questionnaire and places the
 * application in the workflow's first stage (which runs that stage's automations).
 */
class ApplicationIntake
{
    public function __construct(
        private ResumeParser $parser,
        private ScreeningService $screening,
        private PipelineService $pipeline,
    ) {}

    public function submit(JobOpening $job, array $data, ?UploadedFile $resume = null, string $source = 'Careers page'): Application
    {
        return DB::transaction(function () use ($job, $data, $resume, $source) {
            $resumeText = null;
            $resumePath = null;
            $parsed = ['skills' => [], 'years_experience' => null];

            if ($resume) {
                $resumePath = $resume->store('resumes', 'local');
                $resumeText = $this->parser->extractText($resume->getRealPath(), $resume->getClientOriginalExtension());
                $parsed = $this->parser->parse($resumeText);
            }

            $candidate = Candidate::firstOrNew(['email' => strtolower(trim($data['email']))]);
            $isDuplicate = $candidate->exists;
            $candidate->fill(array_filter([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'zip' => $data['zip'] ?? null,
                'linkedin_url' => $data['linkedin_url'] ?? null,
                'source' => $candidate->source ?? $source,
                'resume_path' => $resumePath ?? $candidate->resume_path,
                'resume_text' => $resumeText ?: $candidate->resume_text,
                'years_experience' => $parsed['years_experience'] ?? $candidate->years_experience,
                'skills' => array_values(array_unique(array_merge($candidate->skills ?? [], $parsed['skills'] ?? []))),
                'sms_opt_in' => (bool) ($data['sms_opt_in'] ?? true),
            ], fn ($v) => $v !== null));
            $candidate->save();

            if ($existing = Application::where('candidate_id', $candidate->id)->where('job_opening_id', $job->id)->first()) {
                return $existing;
            }

            $answers = $data['answers'] ?? [];
            $result = $this->screening->score($job->questionnaire, $answers, $candidate->resume_text);

            $application = Application::create([
                'candidate_id' => $candidate->id,
                'job_opening_id' => $job->id,
                'source' => $source,
                'score' => $result['score'],
                'knocked_out' => $result['knocked_out'],
                'answers' => ['values' => $answers, 'breakdown' => $result['breakdown']],
                'eeo' => array_filter($data['eeo'] ?? []),
                'cover_letter' => $data['cover_letter'] ?? null,
                'applied_at' => now(),
            ]);

            Activity::log("Applied via {$source}".($result['score'] !== null ? " · screening score {$result['score']}" : ''), $application, 'applied');
            if ($isDuplicate) {
                Activity::log('Matched an existing candidate profile by email', $application, 'event');
            }

            if ($first = $job->workflow?->firstStage()) {
                $this->pipeline->moveTo($application, $first);
            }

            return $application->fresh();
        });
    }
}
