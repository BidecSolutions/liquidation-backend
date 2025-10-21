<?php

namespace App\Enums;

enum BlogsType: string
{
    case HOME = 'home';
    case MARKETPLACE = 'marketplace';
    case SERVICES = 'services';
    case MOTORS = 'motors';
    case PROPERTY = 'property';
    case JOBS = 'jobs';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
