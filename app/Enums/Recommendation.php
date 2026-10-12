<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Recommendation: string implements HasColor, HasLabel
{
    case StrongHire = 'strong_hire';
    case Hire = 'hire';
    case NoHire = 'no_hire';
    case StrongNoHire = 'strong_no_hire';

    public function getLabel(): string
    {
        return match ($this) {
            self::StrongHire => 'Strong hire',
            self::Hire => 'Hire',
            self::NoHire => 'No hire',
            self::StrongNoHire => 'Strong no hire',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::StrongHire => 'success',
            self::Hire => 'success',
            self::NoHire => 'warning',
            self::StrongNoHire => 'danger',
        };
    }
}
