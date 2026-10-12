<?php

namespace App\Filament\Resources\Candidates\RelationManagers;

use App\Models\Message;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Messages';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                TextColumn::make('channel')->badge(),
                TextColumn::make('direction')->badge()->color(fn ($state) => $state === 'inbound' ? 'success' : 'gray'),
                TextColumn::make('subject')
                    ->label('Message')
                    ->state(fn (Message $r) => $r->subject ?: $r->body)
                    ->description(fn (Message $r) => $r->subject ? \Illuminate\Support\Str::limit($r->body, 90) : null)
                    ->wrap()
                    ->limit(80),
                IconColumn::make('automated')->boolean()->trueIcon('heroicon-o-bolt')->falseIcon('heroicon-o-user'),
                TextColumn::make('sent_at')->label('Sent')->since()->sortable(),
            ])
            ->defaultSort('sent_at', 'desc');
    }
}
