<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Location;
use Illuminate\Contracts\Validation\Rule;

use function __;
use function is_int;

class MapLevel implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return is_int($value) && $value >= 1 && $value <= Location::GEOHASH_LENGTH;
    }

    public function message(): string {
        return __('validation.map_level', [
            'min' => 1,
            'max' => Location::GEOHASH_LENGTH,
        ]);
    }
}
