<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmploymentType: string implements HasColor, HasLabel
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case PerDiem = 'per_diem';
    case Contract = 'contract';
    case Internship = 'internship';

    public function getLabel(): string
    {
        return match ($this) {
            self::FullTime => 'Full-time',
            self::PartTime => 'Part-time',
            self::PerDiem => 'Per diem',
            self::Contract => 'Contract',
            self::Internship => 'Internship',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::FullTime => 'primary',
            self::PartTime => 'info',
            self::PerDiem => 'warning',
            self::Contract => 'gray',
            self::Internship => 'gray',
        };
    }
}
