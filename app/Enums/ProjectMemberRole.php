<?php

namespace App\Enums;

enum ProjectMemberRole: string
{
    case PROJECT_MANAGER = 'PROJECT_MANAGER';
    case PROJECT_SUPERVISOR = 'PROJECT_SUPERVISOR';
    case MEMBER = 'MEMBER';
    case VIEWER = 'VIEWER';

    public function label(): string
    {
        return match ($this) {
            self::PROJECT_MANAGER => 'مدیر پروژه',
            self::PROJECT_SUPERVISOR => 'ناظر پروژه',
            self::MEMBER => 'عضو پروژه',
            self::VIEWER => 'مشاهده‌کننده',
        };
    }
}
