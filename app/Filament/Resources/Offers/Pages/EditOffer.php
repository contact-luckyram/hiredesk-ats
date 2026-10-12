<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Enums\OfferStatus;
use App\Filament\Resources\Offers\OfferResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditOffer extends EditRecord
{
    protected static string $resource = OfferResource::class;

    public function getTitle(): string | Htmlable
    {
        return 'Offer · '.$this->getRecord()->application->candidate->name;
    }

    protected function getHeaderActions(): array
    {
        return OfferResource::workflowActions(fn () => $this->getRecord()->refresh());
    }

    protected function getFormActions(): array
    {
        return $this->getRecord()->status === OfferStatus::Signed ? [] : parent::getFormActions();
    }
}
