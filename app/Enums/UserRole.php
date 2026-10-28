<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case ORGANIZATION_MANAGER = 'ORGANIZATION_MANAGER';
    case UNIT_MANAGER = 'UNIT_MANAGER';
    case TEAM_LEAD = 'TEAM_LEAD';
    case PROJECT_MANAGER = 'PROJECT_MANAGER';
    case EXPERT = 'EXPERT';
    case MEMBER = 'MEMBER';
    case VIEWER = 'VIEWER';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'مدیر ارشد سیستم',
            self::ORGANIZATION_MANAGER => 'مدیر سازمان',
            self::UNIT_MANAGER => 'مدیر واحد',
            self::TEAM_LEAD => 'سرپرست تیم',
            self::PROJECT_MANAGER => 'مدیر پروژه',
            self::EXPERT => 'کارشناس',
            self::MEMBER => 'عضو',
            self::VIEWER => 'مشاهده‌کننده',
        };
    }
}
