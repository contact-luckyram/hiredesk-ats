<?php

namespace App\Filament\Resources\Interviews;

use App\Enums\InterviewStatus;
use App\Enums\InterviewType;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Filament\Resources\Interviews\Pages\ListInterviews;
use App\Models\Application;
use App\Models\Interview;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use UnitEnum;

class InterviewResource extends Resource
{
    protected static ?string $model = Interview::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string | UnitEnum | null $navigationGroup = 'Recruit';

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $today = Interview::whereDate('scheduled_at', today())->count();

        return $today ? (string) $today : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Interviews today';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('application_id')
                ->label('Candidate')
                ->options(fn () => Application::active()->with(['candidate', 'jobOpening'])->get()->mapWithKeys(fn ($a) => [$a->id => $a->title()]))
                ->searchable()
                ->required()
                ->columnSpanFull(),
            TextInput::make('title')->required()->default('Interview'),
            Select::make('type')->options(InterviewType::class)->required()->default('video')->native(false),
            DateTimePicker::make('scheduled_at')->required()->seconds(false)->minutesStep(15)->native(false),
            Select::make('duration_minutes')->options([15 => '15 min', 30 => '30 min', 45 => '45 min', 60 => '1 hour', 90 => '90 min'])->default(45)->native(false),
            TextInput::make('location'),
            Select::make('status')->options(InterviewStatus::class)->default('scheduled')->required()->native(false),
            Select::make('interviewer_ids')->label('Interviewers')->multiple()->options(fn () => User::orderBy('name')->pluck('name', 'id'))->columnSpanFull(),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['application.candidate', 'application.jobOpening']))
            ->columns([
                TextColumn::make('scheduled_at')->label('When')->dateTime('D M j, g:i A')->sortable()
                    ->description(fn (Interview $r) => $r->duration_minutes.' min · '.$r->scheduled_at->diffForHumans()),
                TextColumn::make('application.candidate.first_name')->label('Candidate')
                    ->formatStateUsing(fn (Interview $r) => $r->application->candidate->name)
                    ->description(fn (Interview $r) => $r->application->jobOpening->title)
                    ->weight('semibold')
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('title')->description(fn (Interview $r) => $r->location),
                TextColumn::make('type')->badge(),
                TextColumn::make('interviewers')->label('Interviewers')->state(fn (Interview $r) => $r->interviewers()->pluck('name')->all())->badge()->color('gray'),
                TextColumn::make('status')->badge(),
            ])
            ->defaultSort('scheduled_at')
            ->groups([
                Group::make('scheduled_at')->label('Day')->date()->collapsible(),
            ])
            ->defaultGroup('scheduled_at')
            ->filters([
                SelectFilter::make('status')->options(InterviewStatus::class),
                SelectFilter::make('type')->options(InterviewType::class),
            ])
            ->recordUrl(fn (Interview $r) => ApplicationResource::getUrl('view', ['record' => $r->application_id]))
            ->recordActions([
                Action::make('done')
                    ->label('Complete')
                    ->icon(Heroicon::OutlinedCheck)
                    ->color('success')
                    ->visible(fn (Interview $r) => $r->status === InterviewStatus::Scheduled)
                    ->action(fn (Interview $r) => $r->update(['status' => InterviewStatus::Completed])),
                ActionGroup::make([
                    Action::make('ics')->label('Calendar invite (.ics)')->icon(Heroicon::OutlinedArrowDownTray)->url(fn (Interview $r) => route('interviews.ics', $r)),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListInterviews::route('/')];
    }
}
