<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MessageChannel: string implements HasColor, HasLabel
{
    case Email = 'email';
    case Sms = 'sms';

    public function getLabel(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Sms => 'Text (SMS)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Email => 'primary',
            self::Sms => 'success',
        };
    }
}
