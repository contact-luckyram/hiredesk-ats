<?php

namespace App\Filament\Resources\Candidates;

use App\Filament\Resources\Candidates\Pages\CreateCandidate;
use App\Filament\Resources\Candidates\Pages\EditCandidate;
use App\Filament\Resources\Candidates\Pages\ListCandidates;
use App\Filament\Resources\Candidates\Pages\ViewCandidate;
use App\Filament\Resources\Candidates\RelationManagers\ApplicationsRelationManager;
use App\Filament\Resources\Candidates\RelationManagers\MessagesRelationManager;
use App\Models\Candidate;
use App\Models\JobOpening;
use App\Models\ZipCode;
use App\Support\BooleanSearch;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class CandidateResource extends Resource
{
    protected static ?string $model = Candidate::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string | UnitEnum | null $navigationGroup = 'Recruit';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'email';

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'phone'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return ['Email' => $record->email, 'Location' => $record->locationLabel()];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Section::make('Contact')
                    ->columnSpan(['lg' => 2])
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('first_name')->required()->maxLength(60),
                        TextInput::make('last_name')->required()->maxLength(60),
                        TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                        TextInput::make('phone')->tel(),
                        TextInput::make('city'),
                        TextInput::make('state')->length(2)->default('MA'),
                        TextInput::make('zip')->label('ZIP code')->maxLength(10),
                        TextInput::make('linkedin_url')->label('LinkedIn')->url(),
                        TextInput::make('current_title'),
                        TextInput::make('years_experience')->numeric()->integer()->minValue(0),
                    ]),
                Section::make('Profile')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Select::make('source')
                            ->options(array_combine($s = ['Careers page', 'Indeed', 'LinkedIn', 'ZipRecruiter', 'Glassdoor', 'Referral', 'Job fair', 'Agency', 'Other'], $s))
                            ->default('Referral')
                            ->native(false),
                        TagsInput::make('skills'),
                        TagsInput::make('tags'),
                        Toggle::make('in_talent_pool')->label('In talent pool'),
                        Toggle::make('sms_opt_in')->label('OK to text')->default(true),
                        FileUpload::make('resume_path')
                            ->label('Resume')
                            ->disk('local')
                            ->directory('resumes')
                            ->acceptedFileTypes(['application/pdf', 'text/plain', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(5120)
                            ->helperText('PDF, DOCX or TXT. We extract skills automatically.'),
                        Select::make('apply_to_job')
                            ->label('Add to job')
                            ->options(fn () => JobOpening::published()->orderBy('title')->pluck('title', 'id'))
                            ->dehydrated(false)
                            ->visibleOn('create')
                            ->native(false),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(['default' => 1, 'lg' => 3])
            ->components([
                Section::make('Contact')
                    ->columnSpan(['lg' => 2])
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        TextEntry::make('email')->copyable()->icon(Heroicon::OutlinedEnvelope),
                        TextEntry::make('phone')->placeholder('—')->icon(Heroicon::OutlinedPhone),
                        TextEntry::make('location')->state(fn (Candidate $r) => $r->locationLabel())->placeholder('—')->icon(Heroicon::OutlinedMapPin),
                        TextEntry::make('current_title')->placeholder('—'),
                        TextEntry::make('years_experience')->suffix(' years')->placeholder('—'),
                        TextEntry::make('source')->badge()->color('gray'),
                        TextEntry::make('linkedin_url')->label('LinkedIn')->url(fn ($state) => $state, true)->placeholder('—'),
                        TextEntry::make('created_at')->label('In HireDesk since')->date('M j, Y'),
                    ]),
                Section::make('Skills & tags')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        TextEntry::make('skills')->badge()->placeholder('None found'),
                        TextEntry::make('tags')->badge()->color('warning')->placeholder('—'),
                        TextEntry::make('in_talent_pool')->label('Talent pool')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')->badge()->color(fn ($state) => $state ? 'success' : 'gray'),
                    ]),
                Section::make('Resume')
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('resume_text')->hiddenLabel()->placeholder('No resume text on file.')->extraAttributes(['class' => 'hd-resume']),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Candidate')
                    ->weight('semibold')
                    ->description(fn (Candidate $r) => $r->email)
                    ->searchable(['first_name', 'last_name', 'email'])
                    ->sortable(['last_name']),
                TextColumn::make('current_title')->placeholder('—')->toggleable(),
                TextColumn::make('city')->label('Location')->formatStateUsing(fn (Candidate $r) => $r->locationLabel())->toggleable(),
                TextColumn::make('skills')->badge()->limitList(3)->expandableLimitedList()->toggleable(),
                TextColumn::make('source')->badge()->color('gray')->sortable(),
                TextColumn::make('applications_count')->counts('applications')->label('Applications')->badge()->sortable(),
                IconColumn::make('in_talent_pool')->label('Pool')->boolean()->trueIcon(Heroicon::OutlinedStar)->falseIcon(Heroicon::OutlinedMinus)->toggleable(),
                TextColumn::make('created_at')->label('Added')->since()->sortable()->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('boolean')
                    ->label('Keyword search')
                    ->schema([
                        TextInput::make('q')
                            ->label('Boolean keyword search')
                            ->placeholder('ICU AND (ACLS OR BLS) NOT travel')
                            ->helperText('Searches name, title, skills and resume text. Use AND, OR, NOT, "quotes".'),
                    ])
                    ->query(fn (Builder $query, array $data) => filled($data['q'] ?? null)
                        ? BooleanSearch::apply($query, $data['q'], ['first_name', 'last_name', 'current_title', 'skills', 'resume_text'])
                        : $query)
                    ->indicateUsing(fn (array $data) => filled($data['q'] ?? null) ? 'Keywords: '.$data['q'] : null),
                Filter::make('radius')
                    ->label('Distance')
                    ->schema([
                        TextInput::make('zip')->label('Within miles of ZIP')->placeholder('02115'),
                        Select::make('miles')->options([5 => '5 miles', 10 => '10 miles', 25 => '25 miles', 50 => '50 miles'])->default(25)->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $origin = filled($data['zip'] ?? null) ? ZipCode::find(trim($data['zip'])) : null;
                        if (! $origin) {
                            return $query;
                        }
                        $zips = ZipCode::all()->filter(fn (ZipCode $z) => ZipCode::miles($origin->latitude, $origin->longitude, $z->latitude, $z->longitude) <= (int) ($data['miles'] ?? 25))->pluck('zip');

                        return $query->whereIn('zip', $zips);
                    })
                    ->indicateUsing(fn (array $data) => filled($data['zip'] ?? null) ? "Within {$data['miles']} mi of {$data['zip']}" : null),
                TernaryFilter::make('in_talent_pool')->label('Talent pool'),
                SelectFilter::make('source')->options(fn () => Candidate::query()->whereNotNull('source')->distinct()->orderBy('source')->pluck('source', 'source')->all()),
            ])
            ->filtersFormColumns(2)
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('pool')
                        ->label('Add to talent pool')
                        ->icon(Heroicon::OutlinedStar)
                        ->action(fn (Collection $records) => $records->each->update(['in_talent_pool' => true]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ApplicationsRelationManager::class,
            MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCandidates::route('/'),
            'create' => CreateCandidate::route('/create'),
            'view' => ViewCandidate::route('/{record}'),
            'edit' => EditCandidate::route('/{record}/edit'),
        ];
    }
}
