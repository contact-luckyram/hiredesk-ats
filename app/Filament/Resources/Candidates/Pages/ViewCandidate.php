<?php

namespace App\Filament\Resources\Candidates\Pages;

use App\Enums\MessageChannel;
use App\Filament\Actions\CandidateActions;
use App\Filament\Resources\Candidates\CandidateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewCandidate extends ViewRecord
{
    protected static string $resource = CandidateResource::class;

    public function getTitle(): string | Htmlable
    {
        return $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        $resolve = fn () => [$this->getRecord(), null];

        return [
            CandidateActions::message(MessageChannel::Email, $resolve),
            CandidateActions::message(MessageChannel::Sms, $resolve),
            CandidateActions::addToJob(fn () => $this->getRecord()),
            EditAction::make(),
        ];
    }
}
