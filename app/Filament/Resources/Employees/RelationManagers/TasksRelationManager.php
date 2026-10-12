<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Enums\TaskAssignee;
use App\Enums\TaskType;
use App\Models\Activity;
use App\Models\OnboardingTask;
use App\Services\OnboardingService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Onboarding checklist';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->required()->columnSpanFull(),
            Select::make('type')->options(TaskType::class)->required()->default('task')->native(false),
            Select::make('assignee')->options(TaskAssignee::class)->required()->default('hr')->native(false),
            DatePicker::make('due_date')->native(false),
            Textarea::make('description')->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->reorderable('sort')
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('title')
                    ->weight('semibold')
                    ->description(fn (OnboardingTask $r) => $r->description)
                    ->wrap(),
                TextColumn::make('type')->badge(),
                TextColumn::make('assignee')->badge(),
                TextColumn::make('due_date')->date('M j')->sortable()
                    ->color(fn (OnboardingTask $r) => $r->isOverdue() ? 'danger' : null)
                    ->description(fn (OnboardingTask $r) => $r->isOverdue() ? 'Overdue' : null),
                TextColumn::make('completed_at')
                    ->label('Status')
                    ->state(fn (OnboardingTask $r) => $r->isDone() ? 'Done '.$r->completed_at->format('M j') : 'To do')
                    ->badge()
                    ->color(fn (OnboardingTask $r) => $r->isDone() ? 'success' : ($r->isOverdue() ? 'danger' : 'warning')),
            ])
            ->filters([SelectFilter::make('assignee')->options(TaskAssignee::class)])
            ->headerActions([CreateAction::make()->label('Add task')])
            ->recordActions([
                Action::make('complete')
                    ->label(fn (OnboardingTask $r) => $r->isDone() ? 'Reopen' : 'Complete')
                    ->icon(fn (OnboardingTask $r) => $r->isDone() ? Heroicon::OutlinedArrowUturnLeft : Heroicon::OutlinedCheck)
                    ->color(fn (OnboardingTask $r) => $r->isDone() ? 'gray' : 'success')
                    ->action(function (OnboardingTask $r) {
                        $r->update(['completed_at' => $r->isDone() ? null : now()]);
                        Activity::log(($r->isDone() ? 'Completed' : 'Reopened').": {$r->title}", null, 'onboarding', $r->employee);
                        app(OnboardingService::class)->refreshStatus($r->employee);
                    }),
                Action::make('submission')
                    ->label('View')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->visible(fn (OnboardingTask $r) => $r->isDone() && in_array($r->type, [TaskType::Form, TaskType::Document, TaskType::Upload], true))
                    ->modalHeading(fn (OnboardingTask $r) => $r->title)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema(fn (OnboardingTask $r) => [
                        TextEntry::make('submitted')->hiddenLabel()->html()->state(new HtmlString(self::submissionHtml($r))),
                    ]),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    private static function submissionHtml(OnboardingTask $task): string
    {
        $html = '';
        $fields = collect($task->data['fields'] ?? []);
        foreach ($task->data['values'] ?? [] as $key => $value) {
            $label = $fields->firstWhere('name', $key)['label'] ?? ucfirst(str_replace('_', ' ', $key));
            $html .= '<div style="display:flex;gap:12px;padding:6px 0;border-bottom:1px solid rgba(127,127,127,.15)"><span style="min-width:160px;opacity:.65">'.e($label).'</span><b>'.e(is_array($value) ? implode(', ', $value) : $value).'</b></div>';
        }
        if ($task->signed_name) {
            $html .= '<p style="margin-top:12px">Signed by <b>'.e($task->signed_name).'</b> on '.$task->completed_at?->format('M j, Y g:i A').'</p>';
            if ($task->signature) {
                $html .= '<img src="'.e($task->signature).'" alt="Signature" style="max-height:80px;background:#fff;border-radius:8px;padding:6px;margin-top:6px">';
            }
        }
        if ($task->file_path) {
            $html .= '<p style="margin-top:12px"><a style="color:#6366f1;font-weight:600" href="'.route('portal.file', $task).'">Download uploaded file</a></p>';
        }

        return $html ?: '<p>No details captured.</p>';
    }
}
