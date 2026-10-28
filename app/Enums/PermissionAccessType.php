<?php

namespace App\Enums;

enum PermissionAccessType: string
{
    case ALLOW = 'ALLOW';
    case DENY = 'DENY';

    public function label(): string
    {
        return match ($this) {
            self::ALLOW => 'مجاز',
            self::DENY => 'غیرمجاز (سلب دسترسی)',
        };
    }
}
