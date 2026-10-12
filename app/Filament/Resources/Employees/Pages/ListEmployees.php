<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Add new hire')];
    }

    public function getTabs(): array
    {
        return [
            'onboarding' => Tab::make('Onboarding')
                ->badge(Employee::where('status', 'onboarding')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'onboarding')),
            'active' => Tab::make('Active')->modifyQueryUsing(fn (Builder $q) => $q->where('status', 'active')),
            'all' => Tab::make('All'),
        ];
    }
}
