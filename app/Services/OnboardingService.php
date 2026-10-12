<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Employee;
use App\Models\OnboardingTemplate;
use Illuminate\Support\Carbon;

/** Turns a hired candidate into an employee with an onboarding checklist and portal link. */
class OnboardingService
{
    public function startFromApplication(Application $application, ?OnboardingTemplate $template = null, ?Carbon $startDate = null): Employee
    {
        if ($existing = $application->employee()->first()) {
            return $existing;
        }

        $candidate = $application->candidate;
        $job = $application->jobOpening;
        $startDate ??= $application->offer?->start_date ?? now()->addWeeks(2)->startOfWeek();

        $employee = Employee::create([
            'candidate_id' => $candidate->id,
            'application_id' => $application->id,
            'department_id' => $job->department_id,
            'location_id' => $job->location_id,
            'first_name' => $candidate->first_name,
            'last_name' => $candidate->last_name,
            'email' => $candidate->email,
            'phone' => $candidate->phone,
            'job_title' => $job->title,
            'manager_name' => $job->hiringManager?->name,
            'start_date' => $startDate,
            'status' => EmployeeStatus::Onboarding,
        ]);

        $this->applyTemplate($employee, $template ?? OnboardingTemplate::default());

        Activity::log("Onboarding started for {$employee->name}", $application, 'onboarding', $employee);

        return $employee;
    }

    public function applyTemplate(Employee $employee, ?OnboardingTemplate $template): void
    {
        if (! $template) {
            return;
        }

        $employee->update(['onboarding_template_id' => $template->id]);
        $sort = (int) $employee->tasks()->max('sort');

        foreach ($template->tasks ?? [] as $task) {
            $employee->tasks()->create([
                'title' => $task['title'],
                'description' => $task['description'] ?? null,
                'type' => $task['type'] ?? 'task',
                'assignee' => $task['assignee'] ?? 'new_hire',
                'sort' => ++$sort,
                'due_date' => $employee->start_date->clone()->addDays((int) ($task['due_offset_days'] ?? 0)),
                'data' => ['fields' => $task['fields'] ?? [], 'document' => $task['document'] ?? null],
            ]);
        }
    }

    /** Marks the employee active once every task is done. */
    public function refreshStatus(Employee $employee): void
    {
        $employee->load('tasks');
        if ($employee->status === EmployeeStatus::Onboarding && $employee->tasks->isNotEmpty() && $employee->tasks->every->isDone()) {
            $employee->update(['status' => EmployeeStatus::Active]);
            Activity::log("{$employee->name} completed onboarding", null, 'onboarding', $employee);
        }
    }
}
