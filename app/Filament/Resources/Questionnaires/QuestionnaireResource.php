<?php

namespace App\Filament\Resources\Questionnaires;

use App\Filament\Resources\Questionnaires\Pages\CreateQuestionnaire;
use App\Filament\Resources\Questionnaires\Pages\EditQuestionnaire;
use App\Filament\Resources\Questionnaires\Pages\ListQuestionnaires;
use App\Models\Questionnaire;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/** Pre-screening questionnaires with knock-out questions and weighted scoring. */
class QuestionnaireResource extends Resource
{
    protected static ?string $model = Questionnaire::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string | UnitEnum | null $navigationGroup = 'Recruit';

    protected static ?string $navigationLabel = 'Questionnaires';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Questionnaire')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(100),
                        TextInput::make('description')->maxLength(160),
                    ]),
                Section::make('Questions')
                    ->description('Knock-out questions screen out applicants who give the wrong answer. Points add up to a 0–100 score.')
                    ->schema([
                        Repeater::make('questions')
                            ->hiddenLabel()
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->addActionLabel('Add question')
                            ->itemLabel(fn (array $state) => ($state['label'] ?? 'New question').(! empty($state['knockout']) ? '  · knock-out' : '').(isset($state['weight']) ? '  · '.$state['weight'].' pts' : ''))
                            ->columns(['default' => 1, 'md' => 4])
                            ->schema([
                                TextInput::make('label')->label('Question')->required()->columnSpan(['md' => 3])
                                    ->live(onBlur: true),
                                Select::make('type')
                                    ->options(['yes_no' => 'Yes / No', 'choice' => 'Multiple choice', 'number' => 'Number', 'text' => 'Free text'])
                                    ->default('yes_no')
                                    ->required()
                                    ->live()
                                    ->native(false),
                                TagsInput::make('options')
                                    ->placeholder('Add an option')
                                    ->columnSpan(['md' => 2])
                                    ->visible(fn (Get $get) => $get('type') === 'choice'),
                                TextInput::make('ideal')
                                    ->label(fn (Get $get) => match ($get('type')) {
                                        'number' => 'Minimum value',
                                        'choice' => 'Preferred option',
                                        default => 'Preferred answer',
                                    })
                                    ->default('yes')
                                    ->hidden(fn (Get $get) => $get('type') === 'text'),
                                TextInput::make('weight')->label('Points')->numeric()->integer()->minValue(0)->default(10)->required(),
                                Toggle::make('knockout')->label('Knock-out')->inline(false),
                                Hidden::make('key')
                                    ->dehydrateStateUsing(fn (?string $state, Get $get) => $state ?: Str::slug((string) $get('label'), '_')),
                            ]),
                    ]),
                Section::make('Resume keyword scoring')
                    ->columns(['default' => 1, 'md' => 4])
                    ->schema([
                        TagsInput::make('keywords')->placeholder('e.g. ICU, ACLS, Epic')->columnSpan(['md' => 3]),
                        TextInput::make('keyword_points')->label('Points for keywords')->numeric()->integer()->minValue(0)->default(0),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->weight('semibold')->description(fn (Questionnaire $r) => $r->description)->searchable(),
                TextColumn::make('question_count')->label('Questions')->state(fn (Questionnaire $r) => count($r->questions ?? []))->badge()->color('gray'),
                TextColumn::make('knockouts')->label('Knock-outs')->state(fn (Questionnaire $r) => collect($r->questions ?? [])->where('knockout', true)->count())->badge()->color('danger'),
                TextColumn::make('job_openings_count')->counts('jobOpenings')->label('Jobs')->badge()->color('gray'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestionnaires::route('/'),
            'create' => CreateQuestionnaire::route('/create'),
            'edit' => EditQuestionnaire::route('/{record}/edit'),
        ];
    }
}
