<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StageCategory: string implements HasColor, HasLabel
{
    case Applied = 'applied';
    case Review = 'review';
    case Interview = 'interview';
    case Offer = 'offer';
    case Hired = 'hired';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Applied => 'Applied',
            self::Review => 'In review',
            self::Interview => 'Interview',
            self::Offer => 'Offer',
            self::Hired => 'Hired',
            self::Rejected => 'Rejected',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Applied => 'gray',
            self::Review => 'info',
            self::Interview => 'primary',
            self::Offer => 'warning',
            self::Hired => 'success',
            self::Rejected => 'danger',
        };
    }

    /** Hex colour used for this category in charts and the pipeline. */
    public function hex(): string
    {
        return match ($this) {
            self::Applied => '#64748b',
            self::Review => '#0ea5e9',
            self::Interview => '#6366f1',
            self::Offer => '#f59e0b',
            self::Hired => '#10b981',
            self::Rejected => '#ef4444',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Hired, self::Rejected], true);
    }
}
