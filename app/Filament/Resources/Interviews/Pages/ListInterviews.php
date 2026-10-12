<?php

namespace App\Filament\Resources\Interviews\Pages;

use App\Filament\Resources\Interviews\InterviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInterviews extends ListRecords
{
    protected static string $resource = InterviewResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Schedule interview')];
    }

    public function getTabs(): array
    {
        return [
            'upcoming' => Tab::make('Upcoming')->modifyQueryUsing(fn (Builder $query) => $query->where('scheduled_at', '>=', today())),
            'week' => Tab::make('This week')->modifyQueryUsing(fn (Builder $query) => $query->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])),
            'past' => Tab::make('Past')->modifyQueryUsing(fn (Builder $query) => $query->where('scheduled_at', '<', today())->reorder('scheduled_at', 'desc')),
            'all' => Tab::make('All'),
        ];
    }
}
