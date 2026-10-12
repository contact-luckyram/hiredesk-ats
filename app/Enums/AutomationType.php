<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AutomationType: string implements HasColor, HasLabel
{
    case Email = 'email';
    case Sms = 'sms';
    case Questionnaire = 'questionnaire';
    case Scorecard = 'scorecard';
    case Task = 'task';
    case AutoReject = 'auto_reject';
    case TalentPool = 'talent_pool';
    case Offer = 'offer';
    case BackgroundCheck = 'background_check';
    case Onboarding = 'onboarding';

    public function getLabel(): string
    {
        return match ($this) {
            self::Email => 'Send email',
            self::Sms => 'Send text (SMS)',
            self::Questionnaire => 'Send questionnaire',
            self::Scorecard => 'Request scorecards',
            self::Task => 'Create recruiter task',
            self::AutoReject => 'Auto-reject low score',
            self::TalentPool => 'Add to talent pool',
            self::Offer => 'Draft offer letter',
            self::BackgroundCheck => 'Order background check (demo)',
            self::Onboarding => 'Start onboarding',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Email => 'primary',
            self::Sms => 'success',
            self::Questionnaire => 'info',
            self::Scorecard => 'warning',
            self::Task => 'gray',
            self::AutoReject => 'danger',
            self::TalentPool => 'info',
            self::Offer => 'warning',
            self::BackgroundCheck => 'gray',
            self::Onboarding => 'success',
        };
    }
}
