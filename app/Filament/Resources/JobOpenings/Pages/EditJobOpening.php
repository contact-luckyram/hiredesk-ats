<?php

namespace App\Filament\Resources\JobOpenings\Pages;

use App\Enums\JobStatus;
use App\Filament\Pages\Pipeline;
use App\Filament\Resources\JobOpenings\JobOpeningResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditJobOpening extends EditRecord
{
    protected static string $resource = JobOpeningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pipeline')
                ->icon(Heroicon::OutlinedViewColumns)
                ->color('gray')
                ->url(fn () => Pipeline::getUrl(['job' => $this->getRecord()->id])),
            Action::make('careers')
                ->label('Careers page')
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->color('gray')
                ->visible(fn () => $this->getRecord()->status === JobStatus::Published)
                ->url(fn () => route('careers.show', $this->getRecord()), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
