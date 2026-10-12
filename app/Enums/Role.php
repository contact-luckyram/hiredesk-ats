<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasColor, HasLabel
{
    case Admin = 'admin';
    case Recruiter = 'recruiter';
    case HiringManager = 'hiring_manager';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Recruiter => 'Recruiter',
            self::HiringManager => 'Hiring manager',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Recruiter => 'primary',
            self::HiringManager => 'info',
        };
    }
}
