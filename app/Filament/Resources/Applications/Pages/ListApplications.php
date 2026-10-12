<?php

namespace App\Filament\Resources\Applications\Pages;

use App\Filament\Pages\Pipeline;
use App\Filament\Resources\Applications\ApplicationResource;
use App\Models\Application;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('board')->label('Pipeline board')->icon(Heroicon::OutlinedViewColumns)->url(Pipeline::getUrl()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->badge(Application::active()->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNull('rejected_at')->whereNull('hired_at')),
            'hired' => Tab::make('Hired')
                ->badgeColor('success')
                ->badge(Application::whereNotNull('hired_at')->count())
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('hired_at')),
            'rejected' => Tab::make('Not selected')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('rejected_at')),
            'all' => Tab::make('All'),
        ];
    }
}
