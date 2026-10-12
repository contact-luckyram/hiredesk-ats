<?php

namespace App\Filament\Resources\Applications;

use App\Enums\StageCategory;
use App\Filament\Resources\Applications\Pages\ListApplications;
use App\Filament\Resources\Applications\Pages\ViewApplication;
use App\Models\Application;
use App\Models\Stage;
use App\Services\PipelineService;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Applications = a candidate in a job's pipeline. The view page is the full candidate profile. */
class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string | UnitEnum | null $navigationGroup = 'Recruit';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $new = Application::whereHas('stage', fn ($q) => $q->where('category', StageCategory::Applied->value))->count();

        return $new ? (string) $new : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'New applicants to review';
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record?->candidate?->name ?? 'Application';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['candidate', 'jobOpening', 'stage']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('candidate.first_name')
                    ->label('Candidate')
                    ->formatStateUsing(fn (Application $r) => $r->candidate->name)
                    ->description(fn (Application $r) => $r->candidate->email)
                    ->weight('semibold')
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('jobOpening.title')->label('Job')->searchable()->sortable()->wrap(),
                TextColumn::make('stage.name')
                    ->label('Stage')
                    ->badge()
                    ->color(fn (Application $r) => $r->stage?->category->getColor() ?? 'gray')
                    ->description(fn (Application $r) => $r->isOverSla() ? "⏱ {$r->daysInStage()} days in stage" : null),
                TextColumn::make('score')
                    ->label('Score')
                    ->badge()
                    ->sortable()
                    ->color(fn (Application $r) => $r->scoreColor())
                    ->formatStateUsing(fn ($state, Application $r) => $r->knocked_out ? "{$state} · KO" : $state)
                    ->placeholder('—'),
                TextColumn::make('rating')->formatStateUsing(fn ($state) => str_repeat('★', (int) $state))->color('warning')->sortable()->placeholder('—'),
                TextColumn::make('source')->badge()->color('gray')->toggleable(),
                TextColumn::make('applied_at')->label('Applied')->since()->sortable()->color('gray'),
            ])
            ->defaultSort('applied_at', 'desc')
            ->filters([
                SelectFilter::make('job_opening_id')->label('Job')->relationship('jobOpening', 'title')->searchable()->preload(),
                SelectFilter::make('category')
                    ->label('Stage type')
                    ->options(StageCategory::class)
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null) ? $query->whereHas('stage', fn ($s) => $s->where('category', $data['value'])) : $query),
                SelectFilter::make('source')->options(fn () => Application::query()->whereNotNull('source')->distinct()->orderBy('source')->pluck('source', 'source')->all()),
                Filter::make('min_score')
                    ->schema([TextInput::make('min')->label('Minimum score')->numeric()->minValue(0)->maxValue(100)])
                    ->query(fn (Builder $query, array $data) => filled($data['min'] ?? null) ? $query->where('score', '>=', (int) $data['min']) : $query)
                    ->indicateUsing(fn (array $data) => filled($data['min'] ?? null) ? "Score ≥ {$data['min']}" : null),
                TernaryFilter::make('knocked_out')->label('Knock-out'),
            ])
            ->recordActions([ViewAction::make()->label('Open')])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('move')
                        ->label('Move to stage')
                        ->icon(Heroicon::OutlinedArrowRightCircle)
                        ->schema([
                            Select::make('stage_name')
                                ->label('Stage')
                                ->options(fn () => Stage::query()->orderBy('sort')->pluck('name', 'name')->unique())
                                ->required()
                                ->helperText('Matched by name within each job\'s workflow.'),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $moved = 0;
                            foreach ($records as $application) {
                                $stage = Stage::where('workflow_id', $application->jobOpening->workflow_id)->where('name', $data['stage_name'])->first();
                                if ($stage) {
                                    app(PipelineService::class)->moveTo($application, $stage);
                                    $moved++;
                                }
                            }
                            Notification::make()->title("{$moved} moved to {$data['stage_name']}")->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('reject')
                        ->label('Not selected')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(fn ($a) => app(PipelineService::class)->reject($a)))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplications::route('/'),
            'view' => ViewApplication::route('/{record}'),
        ];
    }
}
