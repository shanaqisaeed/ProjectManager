<?php

namespace App\Enums;

enum ProjectMemberStatus: string
{
    case INVITED = 'INVITED';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    case REMOVED = 'REMOVED';

    public function label(): string
    {
        return match ($this) {
            self::INVITED => 'دعوت شده (در انتظار تایید)',
            self::ACCEPTED => 'عضو فعال',
            self::REJECTED => 'رد شده',
            self::REMOVED => 'حذف شده',
        };
    }
}
