<?php

namespace App\Filament\Resources\Workflows;

use App\Enums\AutomationType;
use App\Enums\StageCategory;
use App\Filament\Resources\Workflows\Pages\CreateWorkflow;
use App\Filament\Resources\Workflows\Pages\EditWorkflow;
use App\Filament\Resources\Workflows\Pages\ListWorkflows;
use App\Models\EmailTemplate;
use App\Models\Workflow;
use App\Support\EnumValue;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\ColorPicker;
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
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Hiring workflows and their pipeline stages. Each stage has a colour, a reporting
 * category, an SLA and automations that run when a candidate enters the stage.
 */
class WorkflowResource extends Resource
{
    protected static ?string $model = Workflow::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string | UnitEnum | null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Workflows & stages';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $usesTemplate = fn (Get $get) => in_array(EnumValue::of($get('type')), [AutomationType::Email->value, AutomationType::Sms->value, AutomationType::Questionnaire->value], true);

        return $schema
            ->columns(1)
            ->components([
                Section::make('Workflow')
                    ->columns(3)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(80),
                        TextInput::make('description')->maxLength(160)->columnSpan(2),
                        Toggle::make('is_default')->label('Default for new jobs'),
                    ]),

                Section::make('Pipeline stages')
                    ->description('Drag to reorder. Automations run every time a candidate enters the stage.')
                    ->schema([
                        Repeater::make('stages')
                            ->hiddenLabel()
                            ->relationship()
                            ->orderColumn('sort')
                            ->reorderable()
                            ->collapsible()
                            ->collapsed()
                            ->cloneable()
                            ->defaultItems(0)
                            ->addActionLabel('Add stage')
                            ->itemLabel(fn (array $state): ?string => trim(($state['name'] ?? 'New stage')
                                .(isset($state['category']) && $state['category'] ? ' · '.(StageCategory::tryFrom((string) EnumValue::of($state['category']))?->getLabel()) : '')
                                .(! empty($state['automations']) ? ' · ⚡ '.count($state['automations']) : '')))
                            ->columns(['default' => 1, 'sm' => 2, 'lg' => 5])
                            ->schema([
                                TextInput::make('name')->required()->maxLength(40)->columnSpan(['lg' => 2]),
                                ColorPicker::make('color')->required()->default('#6366f1'),
                                Select::make('category')
                                    ->label('Reports as')
                                    ->options(StageCategory::class)
                                    ->required()
                                    ->default(StageCategory::Review->value)
                                    ->native(false),
                                TextInput::make('sla_days')
                                    ->label('SLA')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->suffix('days')
                                    ->placeholder('None'),
                                Toggle::make('notify_team')->label('Notify hiring team')->default(true)->columnSpan(['lg' => 5]),

                                Repeater::make('automations')
                                    ->label('Automations on entry')
                                    ->columnSpanFull()
                                    ->columns(['default' => 1, 'md' => 3])
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->reorderable()
                                    ->addActionLabel('Add automation')
                                    ->itemLabel(fn (array $state): ?string => AutomationType::tryFrom((string) EnumValue::of($state['type'] ?? ''))?->getLabel() ?? 'New automation')
                                    ->schema([
                                        Select::make('type')
                                            ->options(AutomationType::class)
                                            ->required()
                                            ->live()
                                            ->native(false),
                                        Select::make('template_id')
                                            ->label('Template')
                                            ->options(fn () => EmailTemplate::orderBy('name')->pluck('name', 'id'))
                                            ->placeholder('Default message')
                                            ->visible($usesTemplate)
                                            ->native(false),
                                        TextInput::make('min_score')
                                            ->label('Reject if score below')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->default(50)
                                            ->visible(fn (Get $get) => EnumValue::of($get('type')) === AutomationType::AutoReject->value),
                                        TextInput::make('note')
                                            ->label('Task')
                                            ->placeholder('e.g. Call candidate within 24 hours')
                                            ->visible(fn (Get $get) => EnumValue::of($get('type')) === AutomationType::Task->value),
                                        TextInput::make('subject')
                                            ->placeholder('Uses template subject')
                                            ->visible(fn (Get $get) => in_array(EnumValue::of($get('type')), [AutomationType::Email->value, AutomationType::Questionnaire->value], true) && ! $get('template_id')),
                                        Textarea::make('body')
                                            ->rows(3)
                                            ->placeholder('Merge fields: {first_name} {job_title} {company} {stage}')
                                            ->columnSpanFull()
                                            ->visible(fn (Get $get) => $usesTemplate($get) && ! $get('template_id')),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight('semibold')
                    ->description(fn (Workflow $record) => $record->description)
                    ->searchable(),
                ViewColumn::make('stages')
                    ->label('Stages')
                    ->view('filament.tables.stage-chips'),
                TextColumn::make('job_openings_count')
                    ->label('Jobs')
                    ->counts('jobOpenings')
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_default')->label('Default')->boolean(),
            ])
            ->recordActions([
                EditAction::make()->label('Edit stages'),
                ReplicateAction::make()
                    ->label('Duplicate')
                    ->excludeAttributes(['is_default', 'job_openings_count'])
                    ->beforeReplicaSaved(fn (Workflow $replica) => $replica->name = $replica->name.' (copy)')
                    ->after(function (Workflow $replica, Workflow $record) {
                        foreach ($record->stages as $stage) {
                            $replica->stages()->create($stage->only(['name', 'color', 'category', 'sort', 'sla_days', 'notify_team', 'automations']));
                        }
                    }),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkflows::route('/'),
            'create' => CreateWorkflow::route('/create'),
            'edit' => EditWorkflow::route('/{record}/edit'),
        ];
    }
}
