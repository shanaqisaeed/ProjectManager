<?php

namespace App\Enums;

enum OrganizationNodeType: string
{
    case ORGANIZATION = 'ORGANIZATION';
    case DEPARTMENT = 'DEPARTMENT';
    case UNIT = 'UNIT';
    case TEAM = 'TEAM';
    case GROUP = 'GROUP';

    public function label(): string
    {
        return match ($this) {
            self::ORGANIZATION => 'سازمان',
            self::DEPARTMENT => 'اداره / معاونت',
            self::UNIT => 'واحد',
            self::TEAM => 'تیم',
            self::GROUP => 'گروه',
        };
    }
}
