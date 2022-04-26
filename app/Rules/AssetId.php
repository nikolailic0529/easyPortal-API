<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Asset;
use Illuminate\Contracts\Validation\Rule;

use function __;

class AssetId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $value && Asset::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.asset_id');
    }
}
