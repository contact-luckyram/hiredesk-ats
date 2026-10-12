<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Interview;
use App\Models\OnboardingTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Private files and exports, available only to signed-in staff. */
class AdminFileController extends Controller
{
    public function resume(Candidate $candidate)
    {
        abort_unless($candidate->resume_path && Storage::disk('local')->exists($candidate->resume_path), 404);

        return Storage::disk('local')->download($candidate->resume_path, str($candidate->name)->slug().'-resume.'.pathinfo($candidate->resume_path, PATHINFO_EXTENSION));
    }

    public function onboardingFile(OnboardingTask $task)
    {
        abort_unless($task->file_path && Storage::disk('local')->exists($task->file_path), 404);

        return Storage::disk('local')->download($task->file_path);
    }

    public function ics(Interview $interview)
    {
        $interview->load(['application.candidate', 'application.jobOpening']);

        return response($interview->toIcs(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="interview-'.$interview->id.'.ics"',
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $days = max(1, (int) $request->query('days', 365));

        return response()->streamDownload(function () use ($days) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Candidate', 'Email', 'Phone', 'Job', 'Department', 'Stage', 'Source', 'Score', 'Rating', 'Applied', 'Hired', 'Days to hire']);

            Application::with(['candidate', 'jobOpening.department', 'stage'])
                ->where('applied_at', '>=', now()->subDays($days))
                ->orderByDesc('applied_at')
                ->chunk(200, function ($apps) use ($out) {
                    foreach ($apps as $a) {
                        fputcsv($out, [
                            $a->candidate->name, $a->candidate->email, $a->candidate->phone, $a->jobOpening->title,
                            $a->jobOpening->department?->name, $a->stage?->name, $a->source, $a->score, $a->rating,
                            $a->applied_at?->toDateString(), $a->hired_at?->toDateString(),
                            $a->hired_at ? (int) $a->applied_at->diffInDays($a->hired_at) : null,
                        ]);
                    }
                });
            fclose($out);
        }, 'hiredesk-applications-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
