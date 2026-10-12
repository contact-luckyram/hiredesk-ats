<?php

namespace App\Filament\Resources\Candidates\RelationManagers;

use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Application;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';

    protected static ?string $title = 'Applications';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('jobOpening.title')->label('Job')->weight('semibold'),
                TextColumn::make('stage.name')
                    ->label('Stage')
                    ->badge()
                    ->color(fn (Application $r) => $r->stage?->category->getColor()),
                TextColumn::make('score')->badge()->color(fn (Application $r) => $r->scoreColor())->placeholder('—'),
                TextColumn::make('rating')->formatStateUsing(fn ($state) => str_repeat('★', (int) $state))->color('warning')->placeholder('—'),
                TextColumn::make('source')->color('gray'),
                TextColumn::make('applied_at')->label('Applied')->since()->sortable(),
            ])
            ->defaultSort('applied_at', 'desc')
            ->recordUrl(fn (Application $r) => ApplicationResource::getUrl('view', ['record' => $r]))
            ->recordActions([
                Action::make('open')->label('Open')->icon('heroicon-o-arrow-right')->url(fn (Application $r) => ApplicationResource::getUrl('view', ['record' => $r])),
            ]);
    }
}
