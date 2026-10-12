<?php

namespace App\Http\Controllers;

use App\Enums\TaskAssignee;
use App\Enums\TaskType;
use App\Models\Activity;
use App\Models\Employee;
use App\Models\OnboardingTask;
use App\Services\OnboardingService;
use Illuminate\Http\Request;

/** Mobile-friendly new-hire portal: forms, e-signatures, uploads and training. */
class PortalController extends Controller
{
    private function employee(string $token): Employee
    {
        return Employee::with(['tasks', 'department', 'location'])->where('portal_token', $token)->firstOrFail();
    }

    public function show(Request $request, string $token)
    {
        $employee = $this->employee($token);
        $mine = $employee->tasks->filter(fn ($t) => $t->assignee === TaskAssignee::NewHire)->values();
        $active = $mine->firstWhere('id', (int) $request->query('task')) ?? $mine->first(fn ($t) => ! $t->isDone());

        return view('public.portal', [
            'employee' => $employee,
            'tasks' => $mine,
            'active' => $active,
            'teamTasks' => $employee->tasks->filter(fn ($t) => $t->assignee !== TaskAssignee::NewHire),
            'progress' => $mine->count() ? (int) round($mine->filter->isDone()->count() / $mine->count() * 100) : 100,
        ]);
    }

    public function complete(Request $request, string $token, OnboardingTask $task)
    {
        $employee = $this->employee($token);
        abort_unless($task->employee_id === $employee->id && $task->assignee === TaskAssignee::NewHire, 404);

        $update = ['completed_at' => now()];

        switch ($task->type) {
            case TaskType::Form:
                $rules = [];
                foreach ($task->data['fields'] ?? [] as $field) {
                    $rule = [($field['required'] ?? false) ? 'required' : 'nullable'];
                    $rule[] = match ($field['type'] ?? 'text') {
                        'date' => 'date',
                        'number' => 'numeric',
                        'email' => 'email',
                        default => 'string',
                    };
                    $rule[] = 'max:1000';
                    $rules['values.'.$field['name']] = $rule;
                }
                $values = $request->validate($rules, ['values.*.required' => 'This field is required.'])['values'] ?? [];
                $update['data'] = array_merge($task->data ?? [], ['values' => $values]);
                break;

            case TaskType::Document:
                $data = $request->validate([
                    'signed_name' => ['required', 'string', 'max:120'],
                    'signature' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:400000'],
                    'agree' => ['accepted'],
                ], ['signature.required' => 'Please draw your signature.']);
                $update['signed_name'] = $data['signed_name'];
                $update['signature'] = $data['signature'];
                break;

            case TaskType::Upload:
                $request->validate(['file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,heic,docx', 'max:8192']]);
                $update['file_path'] = $request->file('file')->store("onboarding/{$employee->id}", 'local');
                break;

            default:
                // Training and checklist tasks are simply confirmed.
                break;
        }

        $task->update($update);
        Activity::log("{$employee->first_name} completed “{$task->title}” in the portal", null, 'onboarding', $employee);
        app(OnboardingService::class)->refreshStatus($employee);

        return redirect()->route('portal.show', $token)->with('status', "“{$task->title}” is done. Nice work!");
    }
}
