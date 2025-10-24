<?php

namespace App\Enums;

enum JobProfileVisibility: string
{
    //
    case FULL = 'full';
    case LIMITED = 'limited';
    case HIDDEN = 'hidden';
    

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
