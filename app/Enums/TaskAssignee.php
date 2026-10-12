<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TaskAssignee: string implements HasColor, HasLabel
{
    case NewHire = 'new_hire';
    case Hr = 'hr';
    case It = 'it';
    case Manager = 'manager';

    public function getLabel(): string
    {
        return match ($this) {
            self::NewHire => 'New hire',
            self::Hr => 'HR',
            self::It => 'IT',
            self::Manager => 'Manager',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::NewHire => 'primary',
            self::Hr => 'warning',
            self::It => 'info',
            self::Manager => 'success',
        };
    }
}
