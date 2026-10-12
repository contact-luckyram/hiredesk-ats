<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TaskType: string implements HasColor, HasLabel
{
    case Form = 'form';
    case Document = 'document';
    case Upload = 'upload';
    case Training = 'training';
    case Task = 'task';

    public function getLabel(): string
    {
        return match ($this) {
            self::Form => 'Form',
            self::Document => 'Sign document',
            self::Upload => 'File upload',
            self::Training => 'Training',
            self::Task => 'Checklist task',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Form => 'primary',
            self::Document => 'warning',
            self::Upload => 'info',
            self::Training => 'success',
            self::Task => 'gray',
        };
    }
}
