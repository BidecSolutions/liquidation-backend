<?php

namespace App\Enums;

enum WorkType : string
{
    //
    case FULL_TIME = 'full_time';
    case PART_TIME = 'part_time';
    case CONTRACT = 'contract';
    case FREELANCE = 'freelance';


    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
