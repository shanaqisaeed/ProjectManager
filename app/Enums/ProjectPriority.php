<?php

namespace App\Enums;

enum ProjectPriority: string
{
    case FORCE_MAJEURE = 'FORCE_MAJEURE';
    case HIGH = 'HIGH';
    case MEDIUM = 'MEDIUM';
    case LOW = 'LOW';

    public function label(): string
    {
        return match ($this) {
            self::FORCE_MAJEURE => 'فورس ماژور / حیاتی',
            self::HIGH => 'اولویت بالا',
            self::MEDIUM => 'اولویت متوسط',
            self::LOW => 'اولویت پایین',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::FORCE_MAJEURE => 'bg-red-600 text-white font-bold animate-pulse ring-2 ring-red-400 shadow-lg shadow-red-500/50',
            self::HIGH => 'bg-amber-500 text-white font-semibold',
            self::MEDIUM => 'bg-blue-500 text-white',
            self::LOW => 'bg-slate-500 text-white opacity-80',
        };
    }

    public function iconClass(): string
    {
        return match ($this) {
            self::FORCE_MAJEURE => 'bi-exclamation-octagon-fill',
            self::HIGH => 'bi-arrow-up-circle-fill',
            self::MEDIUM => 'bi-dash-circle-fill',
            self::LOW => 'bi-arrow-down-circle-fill',
        };
    }
}
