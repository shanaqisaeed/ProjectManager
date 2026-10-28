<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'TODO';
    case IN_PROGRESS = 'IN_PROGRESS';
    case PENDING_REVIEW = 'PENDING_REVIEW';
    case NEEDS_REVISION = 'NEEDS_REVISION';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::TODO => 'در انتظار انجام',
            self::IN_PROGRESS => 'در حال انجام',
            self::PENDING_REVIEW => 'در صف بررسی ناظران',
            self::NEEDS_REVISION => 'نیازمند اصلاح',
            self::COMPLETED => 'تکمیل شده',
            self::CANCELLED => 'لغو شده',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::TODO => 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200',
            self::IN_PROGRESS => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-300',
            self::PENDING_REVIEW => 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-300 animate-pulse',
            self::NEEDS_REVISION => 'bg-rose-100 text-rose-800 dark:bg-rose-900/60 dark:text-rose-300',
            self::COMPLETED => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300',
            self::CANCELLED => 'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-400 line-through',
        };
    }
}
