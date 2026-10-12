<?php

namespace App\Filament\Resources\JobOpenings;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\Role;
use App\Enums\Workplace;
use App\Filament\Pages\Pipeline;
use App\Filament\Resources\JobOpenings\Pages\CreateJobOpening;
use App\Filament\Resources\JobOpenings\Pages\EditJobOpening;
use App\Filament\Resources\JobOpenings\Pages\ListJobOpenings;
use App\Models\JobOpening;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class JobOpeningResource extends Resource
{
    protected static ?string $model = JobOpening::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string | UnitEnum | null $navigationGroup = 'Recruit';

    protected static ?string $navigationLabel = 'Jobs';

    protected static ?string $modelLabel = 'job';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        $pending = JobOpening::where('status', JobStatus::PendingApproval->value)->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Requisitions waiting for approval';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'reference'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Status' => $record->status->getLabel(), 'Location' => $record->locationLabel()];
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return Pipeline::getUrl(['job' => $record->id]);
    }

    public static function form(Schema $schema): Schema
    {
        $team = fn () => User::orderBy('name')->pluck('name', 'id');

        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Group::make()->columnSpan(['lg' => 2])->schema([
                    Section::make('Job details')
                        ->columns(['default' => 1, 'md' => 2])
                        ->schema([
                            TextInput::make('title')->required()->maxLength(120)->columnSpanFull(),
                            Select::make('department_id')
                                ->relationship('department', 'name')
                                ->preload()
                                ->searchable()
                                ->createOptionForm([TextInput::make('name')->required()]),
                            Select::make('location_id')
                                ->relationship('location', 'name')
                                ->preload()
                                ->searchable(),
                            Select::make('employment_type')->options(EmploymentType::class)->required()->default('full_time')->native(false),
                            Select::make('workplace')->options(Workplace::class)->required()->default('on_site')->native(false),
                            TextInput::make('openings')->numeric()->integer()->minValue(1)->default(1)->required(),
                            DatePicker::make('closes_at')->label('Apply by')->native(false),
                        ]),
                    Section::make('Description')
                        ->schema([
                            Textarea::make('summary')
                                ->label('Short summary')
                                ->helperText('Shown in job listings and social shares.')
                                ->rows(2)
                                ->maxLength(300),
                            RichEditor::make('description')
                                ->toolbarButtons(['bold', 'italic', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'undo', 'redo']),
                        ]),
                ]),
                Group::make()->columnSpan(['lg' => 1])->schema([
                    Section::make('Publishing')
                        ->schema([
                            Select::make('status')
                                ->options(JobStatus::class)
                                ->default(JobStatus::Draft->value)
                                ->required()
                                ->native(false)
                                ->helperText('Published jobs appear on the careers site and job board feed.'),
                            Toggle::make('is_featured')->label('Featured (hot job)'),
                        ]),
                    Section::make('Compensation')
                        ->columns(2)
                        ->schema([
                            TextInput::make('salary_min')->label('From')->numeric()->prefix('$'),
                            TextInput::make('salary_max')->label('To')->numeric()->prefix('$'),
                            Select::make('salary_period')
                                ->label('Per')
                                ->options(['year' => 'Year', 'hour' => 'Hour'])
                                ->default('year')
                                ->native(false)
                                ->columnSpanFull(),
                        ]),
                    Section::make('Hiring team & workflow')
                        ->schema([
                            Select::make('hiring_manager_id')->label('Hiring manager')->options($team)->searchable(),
                            Select::make('recruiter_id')->label('Recruiter')->options($team)->searchable()->default(fn () => auth()->id()),
                            Select::make('workflow_id')
                                ->label('Hiring workflow')
                                ->relationship('workflow', 'name')
                                ->preload()
                                ->required()
                                ->default(fn () => \App\Models\Workflow::default()?->id)
                                ->helperText('Defines the pipeline stages and automations.'),
                            Select::make('questionnaire_id')
                                ->label('Screening questionnaire')
                                ->relationship('questionnaire', 'name')
                                ->preload()
                                ->placeholder('None'),
                        ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->weight('semibold')
                    ->searchable()
                    ->sortable()
                    ->description(fn (JobOpening $r) => collect([$r->reference, $r->department?->name, $r->locationLabel()])->filter()->implode(' · ')),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('applications_count')
                    ->label('Applicants')
                    ->counts('applications')
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('employment_type')->label('Type')->badge()->color('gray')->toggleable(),
                TextColumn::make('workflow.name')->label('Workflow')->color('gray')->toggleable(),
                TextColumn::make('hiringManager.name')->label('Hiring manager')->toggleable(),
                TextColumn::make('published_at')->label('Posted')->since()->sortable()->placeholder('—')->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (JobOpening $record) => Pipeline::getUrl(['job' => $record->id]))
            ->filters([
                SelectFilter::make('department')->relationship('department', 'name')->preload(),
                SelectFilter::make('location')->relationship('location', 'name')->preload(),
                SelectFilter::make('employment_type')->label('Type')->options(EmploymentType::class),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn (JobOpening $record) => $record->status === JobStatus::PendingApproval && auth()->user()?->role !== Role::Recruiter)
                    ->requiresConfirmation()
                    ->modalDescription('The job will be published on the careers site and job board feed.')
                    ->action(function (JobOpening $record) {
                        $record->update(['status' => JobStatus::Published]);
                        Notification::make()->title('Job approved and published')->success()->send();
                    }),
                Action::make('pipeline')
                    ->icon(Heroicon::OutlinedViewColumns)
                    ->url(fn (JobOpening $record) => Pipeline::getUrl(['job' => $record->id])),
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('careers')
                        ->label('View on careers site')
                        ->icon(Heroicon::OutlinedGlobeAlt)
                        ->visible(fn (JobOpening $record) => $record->status === JobStatus::Published)
                        ->url(fn (JobOpening $record) => route('careers.show', $record), shouldOpenInNewTab: true),
                    Action::make('close')
                        ->label('Close job')
                        ->icon(Heroicon::OutlinedLockClosed)
                        ->color('danger')
                        ->visible(fn (JobOpening $record) => $record->status === JobStatus::Published)
                        ->requiresConfirmation()
                        ->action(fn (JobOpening $record) => $record->update(['status' => JobStatus::Closed])),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobOpenings::route('/'),
            'create' => CreateJobOpening::route('/create'),
            'edit' => EditJobOpening::route('/{record}/edit'),
        ];
    }
}
