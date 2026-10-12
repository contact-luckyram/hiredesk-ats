<?php

namespace App\Http\Controllers;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\Workplace;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\JobOpening;
use App\Models\ZipCode;
use App\Services\ApplicationIntake;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CareersController extends Controller
{
    public function index(Request $request)
    {
        $jobs = JobOpening::published()->with(['department', 'location'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', '%'.$request->q.'%')
                ->orWhere('summary', 'like', '%'.$request->q.'%')))
            ->when($request->filled('department'), fn ($q) => $q->whereIn('department_id', (array) $request->department))
            ->when($request->filled('type'), fn ($q) => $q->whereIn('employment_type', (array) $request->type))
            ->when($request->filled('workplace'), fn ($q) => $q->whereIn('workplace', (array) $request->workplace))
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->get();

        $origin = $request->filled('zip') ? ZipCode::find(trim((string) $request->zip)) : null;
        $miles = (int) $request->input('miles', 25);
        if ($origin) {
            $jobs = $jobs->map(function (JobOpening $job) use ($origin) {
                $job->distance = $job->location?->latitude
                    ? round(ZipCode::miles($origin->latitude, $origin->longitude, $job->location->latitude, $job->location->longitude))
                    : null;

                return $job;
            })->filter(fn ($job) => $job->distance === null || $job->distance <= $miles)
                ->sortBy(fn ($job) => $job->distance ?? 0)->values();
        }

        return view('public.careers.index', [
            'jobs' => $jobs,
            'departments' => Department::whereHas('jobOpenings', fn ($q) => $q->published())->withCount(['jobOpenings' => fn ($q) => $q->published()])->get(),
            'types' => EmploymentType::cases(),
            'workplaces' => Workplace::cases(),
            'origin' => $origin,
            'zipNotFound' => $request->filled('zip') && ! $origin,
            'total' => JobOpening::published()->count(),
        ]);
    }

    public function show(JobOpening $job)
    {
        abort_unless($job->status === JobStatus::Published, 404);

        return view('public.careers.show', [
            'job' => $job->load(['department', 'location', 'questionnaire']),
            'related' => JobOpening::published()->whereKeyNot($job->id)->where('department_id', $job->department_id)->with('location')->take(3)->get(),
        ]);
    }

    public function apply(JobOpening $job)
    {
        abort_unless($job->status === JobStatus::Published, 404);

        return view('public.careers.apply', ['job' => $job->load(['questionnaire', 'location', 'department'])]);
    }

    public function store(Request $request, JobOpening $job, ApplicationIntake $intake)
    {
        abort_unless($job->status === JobStatus::Published, 404);

        $rules = [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:2'],
            'zip' => ['nullable', 'string', 'max:10'],
            'linkedin_url' => ['nullable', 'url', 'max:200'],
            'resume' => ['nullable', 'file', 'mimes:pdf,docx,txt', 'max:5120'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'sms_opt_in' => ['nullable', 'boolean'],
            'eeo' => ['nullable', 'array'],
            'eeo.*' => ['nullable', 'string', 'max:60'],
            'consent' => ['accepted'],
        ];

        foreach ($job->questionnaire?->questions ?? [] as $q) {
            $key = $q['key'] ?? Str::slug($q['label'], '_');
            $rules["answers.{$key}"] = match ($q['type'] ?? 'yes_no') {
                'number' => ['required', 'numeric', 'min:0', 'max:99'],
                'text' => ['nullable', 'string', 'max:2000'],
                'choice' => ['required', Rule::in($q['options'] ?? [])],
                default => ['required', Rule::in(['yes', 'no'])],
            };
        }

        $data = $request->validate($rules, [
            'consent.accepted' => 'Please confirm your information is accurate.',
            'answers.*.required' => 'Please answer this question.',
        ]);

        $application = $intake->submit($job, $data, $request->file('resume'), $this->source($request));

        return redirect()->route('careers.thanks')->with([
            'applied_job' => $job->title,
            'applied_name' => $application->candidate->first_name,
        ]);
    }

    public function thanks()
    {
        return view('public.careers.thanks', [
            'jobTitle' => session('applied_job'),
            'name' => session('applied_name'),
        ]);
    }

    public function talentPool()
    {
        return view('public.careers.talent-pool', ['departments' => Department::orderBy('name')->get()]);
    }

    public function joinTalentPool(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'zip' => ['nullable', 'string', 'max:10'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['string', 'max:60'],
        ]);

        $candidate = Candidate::firstOrNew(['email' => strtolower($data['email'])]);
        $candidate->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? $candidate->phone,
            'zip' => $data['zip'] ?? $candidate->zip,
            'state' => $candidate->state ?? 'MA',
            'source' => $candidate->source ?? 'Talent pool sign-up',
            'in_talent_pool' => true,
            'tags' => array_values(array_unique(array_merge($candidate->tags ?? [], $data['interests'] ?? []))),
        ])->save();

        return redirect()->route('careers.thanks')->with(['applied_name' => $candidate->first_name, 'applied_job' => null]);
    }

    /** XML job feed in the format Indeed, ZipRecruiter and most aggregators accept. */
    public function feed()
    {
        $jobs = JobOpening::published()->with(['location', 'department'])->latest('published_at')->get();

        return response()->view('public.careers.feed', ['jobs' => $jobs])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function source(Request $request): string
    {
        return match (strtolower((string) $request->query('source', $request->input('source')))) {
            'indeed' => 'Indeed',
            'linkedin' => 'LinkedIn',
            'ziprecruiter' => 'ZipRecruiter',
            'glassdoor' => 'Glassdoor',
            'facebook', 'twitter', 'x' => 'Social media',
            'referral' => 'Referral',
            default => 'Careers page',
        };
    }
}
