<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\OnboardingTemplate;
use App\Services\OnboardingService;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function afterCreate(): void
    {
        if ($template = OnboardingTemplate::find($this->data['template'] ?? null)) {
            app(OnboardingService::class)->applyTemplate($this->getRecord(), $template);
        }
    }

    protected function getRedirectUrl(): string
    {
        return EmployeeResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
