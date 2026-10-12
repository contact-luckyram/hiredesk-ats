<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Filament\Actions\CandidateActions;
use App\Filament\Resources\Candidates\CandidateResource;
use App\Models\JobOpening;
use Filament\Resources\Pages\CreateRecord;

class CreateCandidate extends CreateRecord
{
    protected static string $resource = CandidateResource::class;

    protected function afterCreate(): void
    {
        CandidateActions::parseResume($this->getRecord());

        if ($jobId = $this->data['apply_to_job'] ?? null) {
            CandidateActions::createApplication($this->getRecord(), JobOpening::findOrFail($jobId), $this->getRecord()->source ?? 'Added by recruiter');
        }
    }

    protected function getRedirectUrl(): string
    {
        return CandidateResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
