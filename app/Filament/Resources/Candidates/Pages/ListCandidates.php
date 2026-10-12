<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Filament\Resources\Candidates\CandidateResource;
use App\Models\Candidate;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCandidates extends ListRecords
{
    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Add candidate')];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'new' => Tab::make('New this week')
                ->badge(Candidate::where('created_at', '>=', now()->subWeek())->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->where('created_at', '>=', now()->subWeek())),
            'pool' => Tab::make('Talent pool')
                ->badge(Candidate::where('in_talent_pool', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('in_talent_pool', true)),
        ];
    }
}
