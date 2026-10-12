<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\ManageLocations;
use App\Models\Location;
use App\Models\ZipCode;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string | UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(80)->columnSpanFull(),
            TextInput::make('zip')
                ->label('ZIP code')
                ->maxLength(10)
                ->live(onBlur: true)
                ->helperText('Known ZIP codes fill in the city and map position used for radius search.')
                ->afterStateUpdated(function (?string $state, Set $set) {
                    if ($zip = ZipCode::find($state)) {
                        $set('city', $zip->city);
                        $set('state', $zip->state);
                        $set('latitude', $zip->latitude);
                        $set('longitude', $zip->longitude);
                    }
                }),
            TextInput::make('city')->required()->maxLength(80),
            TextInput::make('state')->required()->length(2)->default('MA'),
            Toggle::make('is_remote')->label('Remote'),
            TextInput::make('latitude')->numeric(),
            TextInput::make('longitude')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->weight('semibold')->searchable(),
                TextColumn::make('city')->formatStateUsing(fn (Location $record) => $record->label())->searchable(),
                TextColumn::make('zip')->label('ZIP')->placeholder('—'),
                IconColumn::make('is_remote')->label('Remote')->boolean(),
                TextColumn::make('job_openings_count')->counts('jobOpenings')->label('Jobs')->badge()->color('gray'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageLocations::route('/')];
    }
}
