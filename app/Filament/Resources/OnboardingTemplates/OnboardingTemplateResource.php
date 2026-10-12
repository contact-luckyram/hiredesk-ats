<?php

namespace App\Filament\Resources\OnboardingTemplates;

use App\Enums\TaskAssignee;
use App\Enums\TaskType;
use App\Filament\Resources\OnboardingTemplates\Pages\CreateOnboardingTemplate;
use App\Filament\Resources\OnboardingTemplates\Pages\EditOnboardingTemplate;
use App\Filament\Resources\OnboardingTemplates\Pages\ListOnboardingTemplates;
use App\Models\OnboardingTemplate;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Reusable onboarding checklists: forms, e-sign documents, uploads, training and HR/IT tasks. */
class OnboardingTemplateResource extends Resource
{
    protected static ?string $model = OnboardingTemplate::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string | UnitEnum | null $navigationGroup = 'Onboard';

    protected static ?string $navigationLabel = 'Onboarding templates';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Template')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(100),
                        TextInput::make('description')->maxLength(160),
                        Toggle::make('is_default')->label('Default template')->inline(false),
                    ]),
                Section::make('Checklist')
                    ->description('Due dates are relative to the start date (negative = before day one).')
                    ->schema([
                        Repeater::make('tasks')
                            ->hiddenLabel()
                            ->reorderable()
                            ->collapsible()
                            ->collapsed()
                            ->cloneable()
                            ->addActionLabel('Add task')
                            ->itemLabel(fn (array $state) => ($state['title'] ?? 'New task').' · '.(TaskType::tryFrom($state['type'] ?? '')?->getLabel() ?? '').' · '.(TaskAssignee::tryFrom($state['assignee'] ?? '')?->getLabel() ?? ''))
                            ->columns(['default' => 1, 'md' => 4])
                            ->schema([
                                TextInput::make('title')->required()->columnSpan(['md' => 2]),
                                Select::make('type')->options(TaskType::class)->required()->default('task')->live()->native(false),
                                Select::make('assignee')->options(TaskAssignee::class)->required()->default('new_hire')->native(false),
                                TextInput::make('due_offset_days')->label('Due (days from start)')->numeric()->integer()->default(-7),
                                TextInput::make('description')->columnSpan(['md' => 3]),
                                Textarea::make('document')
                                    ->label('Document text to sign')
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->visible(fn (Get $get) => $get('type') === TaskType::Document->value),
                                Repeater::make('fields')
                                    ->label('Form fields')
                                    ->columnSpanFull()
                                    ->columns(['default' => 1, 'md' => 4])
                                    ->addActionLabel('Add field')
                                    ->visible(fn (Get $get) => $get('type') === TaskType::Form->value)
                                    ->schema([
                                        TextInput::make('label')->required(),
                                        TextInput::make('name')->label('Key')->required()->alphaDash(),
                                        Select::make('type')->options(['text' => 'Text', 'textarea' => 'Paragraph', 'date' => 'Date', 'number' => 'Number', 'tel' => 'Phone', 'email' => 'Email', 'select' => 'Dropdown'])->default('text')->native(false),
                                        Toggle::make('required')->inline(false),
                                        TextInput::make('options')->label('Dropdown options (comma separated)')->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->weight('semibold')->description(fn (OnboardingTemplate $r) => $r->description)->searchable(),
                TextColumn::make('task_count')->label('Tasks')->state(fn (OnboardingTemplate $r) => count($r->tasks ?? []))->badge()->color('gray'),
                TextColumn::make('signatures')->label('E-sign documents')->state(fn (OnboardingTemplate $r) => collect($r->tasks ?? [])->where('type', 'document')->count())->badge()->color('warning'),
                TextColumn::make('employees_count')->counts('employees')->label('Used by')->badge()->color('gray'),
                IconColumn::make('is_default')->label('Default')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOnboardingTemplates::route('/'),
            'create' => CreateOnboardingTemplate::route('/create'),
            'edit' => EditOnboardingTemplate::route('/{record}/edit'),
        ];
    }
}
