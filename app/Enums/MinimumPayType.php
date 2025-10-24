<?php

namespace App\Enums;

enum MinimumPayType: string
{
    //
    case HOURLY = 'hourly';
    case DAILY = 'daily';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
