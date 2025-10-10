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

    // 🆕 Added conditions (non-duplicate)
    case BRAND_NEW_UNUSED = 'brand_new_unused';
    case LIKE_NEW = 'like_new';
    case GENTLY_USED_EXCELLENT_CONDITION = 'gently_used_excellent_condition';
    case GOOD_CONDITION = 'good_condition';
    case FAIR_CONDITION = 'fair_condition';
    case FOR_PARTS_OR_NOT_WORKING = 'for_parts_or_not_working';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    // 🏷️ Optional: human-readable labels for UI or dropdowns
    public static function labels(): array
    {
        return [
            self::NEW->value => 'New',
            self::USED->value => 'Used',
            self::BRAND_NEW->value => 'Brand New',
            self::READY_TO_MOVE->value => 'Ready to Move',
            self::UNDER_CONSTRUCTION->value => 'Under Construction',
            self::FURNISHED->value => 'Furnished',
            self::SEMI_FURNISHED->value => 'Semi-Furnished',
            self::UNFURNISHED->value => 'Unfurnished',
            self::RECENTLY_RENOVATED->value => 'Recently Renovated',

            // Labels from your provided list
            self::BRAND_NEW_UNUSED->value => 'Brand New / Unused – never opened or used.',
            self::LIKE_NEW->value => 'Like New – opened but looks and works like new.',
            self::GENTLY_USED_EXCELLENT_CONDITION->value => 'Gently Used / Excellent Condition – minor signs of use.',
            self::GOOD_CONDITION->value => 'Good Condition – visible wear but fully functional.',
            self::FAIR_CONDITION->value => 'Fair Condition – heavily used but still works.',
            self::FOR_PARTS_OR_NOT_WORKING->value => 'For Parts or Not Working – damaged or needs repair.',
        ];
    }
}
