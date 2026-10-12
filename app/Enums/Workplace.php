<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Workplace: string implements HasColor, HasLabel
{
    case OnSite = 'on_site';
    case Hybrid = 'hybrid';
    case Remote = 'remote';

    public function getLabel(): string
    {
        return match ($this) {
            self::OnSite => 'On-site',
            self::Hybrid => 'Hybrid',
            self::Remote => 'Remote',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OnSite => 'gray',
            self::Hybrid => 'info',
            self::Remote => 'success',
        };
    }
}
