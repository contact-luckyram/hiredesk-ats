<?php

namespace App\Filament\Resources\Employees;

use App\Enums\EmployeeStatus;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\Employees\Pages\ViewEmployee;
use App\Filament\Resources\Employees\RelationManagers\TasksRelationManager;
use App\Models\Employee;
use App\Models\OnboardingTemplate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string | UnitEnum | null $navigationGroup = 'Onboard';

    protected static ?string $navigationLabel = 'New hires & employees';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'email';

    public static function getNavigationBadge(): ?string
    {
        $n = Employee::where('status', EmployeeStatus::Onboarding->value)->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(['default' => 1, 'md' => 2])->components([
            Section::make('Employee')->columnSpanFull()->columns(['default' => 1, 'md' => 2])->schema([
                TextInput::make('first_name')->required(),
                TextInput::make('last_name')->required(),
                TextInput::make('email')->email()->required(),
                TextInput::make('phone')->tel(),
                TextInput::make('job_title')->required(),
                TextInput::make('manager_name')->label('Manager'),
                Select::make('department_id')->relationship('department', 'name')->preload(),
                Select::make('location_id')->relationship('location', 'name')->preload(),
                DatePicker::make('start_date')->required()->native(false),
                Select::make('status')->options(EmployeeStatus::class)->default('onboarding')->required()->native(false),
                Select::make('template')
                    ->label('Onboarding template')
                    ->options(fn () => OnboardingTemplate::pluck('name', 'id'))
                    ->default(fn () => OnboardingTemplate::default()?->id)
                    ->dehydrated(false)
                    ->visibleOn('create')
                    ->native(false),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(['default' => 1, 'lg' => 3])->components([
            Section::make('Onboarding progress')
                ->columnSpan(['lg' => 2])
                ->schema([
                    ViewEntry::make('progress')->hiddenLabel()->view('filament.employees.progress'),
                ]),
            Section::make('Details')
                ->columnSpan(['lg' => 1])
                ->columns(2)
                ->schema([
                    TextEntry::make('employee_number')->label('Employee #')->copyable(),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('job_title'),
                    TextEntry::make('department.name')->label('Department')->placeholder('—'),
                    TextEntry::make('start_date')->date('M j, Y'),
                    TextEntry::make('manager_name')->label('Manager')->placeholder('—'),
                    TextEntry::make('email')->copyable()->columnSpanFull(),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('location.name')->label('Location')->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($q) => $q->with(['tasks', 'department']))
            ->columns([
                TextColumn::make('first_name')->label('Name')
                    ->formatStateUsing(fn (Employee $r) => $r->name)
                    ->description(fn (Employee $r) => $r->job_title)
                    ->weight('semibold')
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('department.name')->label('Department')->badge()->color('gray')->toggleable(),
                TextColumn::make('start_date')->date('M j, Y')->sortable()->description(fn (Employee $r) => $r->start_date->isFuture() ? 'in '.(int) ceil(now()->diffInDays($r->start_date, true)).' days' : null),
                ViewColumn::make('progress')->label('Onboarding')->view('filament.tables.progress'),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->defaultSort('start_date', 'desc')
            ->filters([SelectFilter::make('status')->options(EmployeeStatus::class), SelectFilter::make('department')->relationship('department', 'name')])
            ->recordActions([
                ViewAction::make(),
                Action::make('portal')->label('Portal')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')->url(fn (Employee $r) => $r->portalUrl(), shouldOpenInNewTab: true),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [TasksRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'view' => ViewEmployee::route('/{record}'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }
}
