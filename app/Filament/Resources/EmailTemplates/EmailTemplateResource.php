<?php

namespace App\Filament\Resources\EmailTemplates;

use App\Enums\MessageChannel;
use App\Filament\Resources\EmailTemplates\Pages\ManageEmailTemplates;
use App\Models\EmailTemplate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string | UnitEnum | null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Email & SMS templates';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(80),
            Select::make('channel')->options(MessageChannel::class)->required()->default('email')->live()->native(false),
            TextInput::make('subject')->maxLength(150)->columnSpanFull()->visible(fn (Get $get) => \App\Support\EnumValue::of($get('channel')) !== MessageChannel::Sms->value),
            Textarea::make('body')
                ->required()
                ->rows(8)
                ->columnSpanFull()
                ->helperText('Merge fields: {first_name}, {last_name}, {job_title}, {company}, {stage}'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->weight('semibold')->searchable(),
                TextColumn::make('channel')->badge(),
                TextColumn::make('subject')->placeholder('—')->limit(60)->color('gray'),
                TextColumn::make('updated_at')->label('Updated')->since()->color('gray'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageEmailTemplates::route('/')];
    }
}
