<?php

namespace App\Enums;

enum TaskPriority: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
    case URGENT = 'URGENT';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'پایین',
            self::MEDIUM => 'متوسط',
            self::HIGH => 'بالا',
            self::URGENT => 'فوری',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::LOW => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            self::MEDIUM => 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-300',
            self::HIGH => 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300',
            self::URGENT => 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300 font-bold',
        };
    }
}
