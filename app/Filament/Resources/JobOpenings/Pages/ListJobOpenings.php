<?php

namespace App\Filament\Resources\JobOpenings\Pages;

use App\Enums\JobStatus;
use App\Filament\Resources\JobOpenings\JobOpeningResource;
use App\Models\JobOpening;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListJobOpenings extends ListRecords
{
    protected static string $resource = JobOpeningResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Post a job')];
    }

    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('All')];
        foreach (JobStatus::cases() as $status) {
            $tabs[$status->value] = Tab::make($status->getLabel())
                ->badge(JobOpening::where('status', $status->value)->count())
                ->badgeColor($status->getColor())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status->value));
        }

        return $tabs;
    }
}
