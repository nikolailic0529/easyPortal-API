<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Team;
use Illuminate\Contracts\Validation\Rule;

use function __;

class TeamId implements Rule {

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return Team::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.team_id');
    }
}
