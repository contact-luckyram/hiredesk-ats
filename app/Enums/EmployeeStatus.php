<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmployeeStatus: string implements HasColor, HasLabel
{
    case Onboarding = 'onboarding';
    case Active = 'active';
    case Terminated = 'terminated';

    public function getLabel(): string
    {
        return match ($this) {
            self::Onboarding => 'Onboarding',
            self::Active => 'Active',
            self::Terminated => 'Terminated',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Onboarding => 'warning',
            self::Active => 'success',
            self::Terminated => 'danger',
        };
    }
}
