<?php

namespace App\Enums;

enum ListingCondition: string
{
    case NEW = 'new';
    case USED = 'used';
    case BRAND_NEW = 'brand_new';
    case READY_TO_MOVE = 'ready_to_move';
    case UNDER_CONSTRUCTION = 'under_construction';
    case FURNISHED = 'furnished';
    case SEMI_FURNISHED = 'semi_furnished';
    case UNFURNISHED = 'unfurnished';
    case RECENTLY_RENOVATED = 'recently_renovated';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
    
}
