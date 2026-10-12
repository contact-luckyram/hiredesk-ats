<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InterviewType: string implements HasColor, HasLabel
{
    case Phone = 'phone';
    case Video = 'video';
    case OnSite = 'on_site';
    case Panel = 'panel';

    public function getLabel(): string
    {
        return match ($this) {
            self::Phone => 'Phone screen',
            self::Video => 'Video',
            self::OnSite => 'On-site',
            self::Panel => 'Panel',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Phone => 'gray',
            self::Video => 'info',
            self::OnSite => 'primary',
            self::Panel => 'warning',
        };
    }
}
