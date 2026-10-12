<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Enums\EmployeeStatus;
use App\Enums\MessageChannel;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Activity;
use App\Models\OnboardingTemplate;
use App\Services\Messenger;
use App\Services\OnboardingService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string | Htmlable
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): ?string
    {
        $e = $this->getRecord();

        return "{$e->job_title} · starts {$e->start_date->format('l, M j, Y')}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('portal')
                ->label('Open new-hire portal')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->url(fn () => $this->getRecord()->portalUrl(), shouldOpenInNewTab: true),
            Action::make('invite')
                ->label('Email portal link')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->visible(fn () => (bool) $this->getRecord()->candidate)
                ->requiresConfirmation()
                ->action(function () {
                    $e = $this->getRecord();
                    app(Messenger::class)->send($e->candidate, MessageChannel::Email,
                        "Hi {$e->first_name},\n\nWelcome to ".config('hiredesk.company')."! Your new-hire portal is ready. Complete your paperwork before {$e->start_date->format('F j')}:\n\n{$e->portalUrl()}\n\nSee you soon!",
                        'Your new-hire portal is ready', $e->application);
                    Notification::make()->title('Portal link sent')->success()->send();
                }),
            ActionGroup::make([
                Action::make('template')
                    ->label('Add tasks from template')
                    ->icon(Heroicon::OutlinedQueueList)
                    ->schema([Select::make('template_id')->label('Template')->options(fn () => OnboardingTemplate::pluck('name', 'id'))->required()])
                    ->action(function (array $data) {
                        app(OnboardingService::class)->applyTemplate($this->getRecord(), OnboardingTemplate::find($data['template_id']));
                        Notification::make()->title('Tasks added')->success()->send();
                    }),
                Action::make('activate')
                    ->label('Mark onboarding complete')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->visible(fn () => $this->getRecord()->status === EmployeeStatus::Onboarding)
                    ->requiresConfirmation()
                    ->action(function () {
                        $this->getRecord()->update(['status' => EmployeeStatus::Active]);
                        Activity::log("{$this->getRecord()->name} marked active", null, 'onboarding', $this->getRecord());
                    }),
                EditAction::make(),
            ])->button()->label('More')->color('gray'),
        ];
    }
}
