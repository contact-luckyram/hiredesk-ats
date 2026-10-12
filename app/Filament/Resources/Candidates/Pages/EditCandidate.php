<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Filament\Actions\CandidateActions;
use App\Filament\Resources\Candidates\CandidateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCandidate extends EditRecord
{
    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }

    protected function afterSave(): void
    {
        if ($this->getRecord()->wasChanged('resume_path')) {
            CandidateActions::parseResume($this->getRecord());
        }
    }
}
