<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Data\Team;
use Illuminate\Contracts\Validation\Rule;

use function trans;

class TeamId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $value && Team::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return trans('validation.team_id');
    }
}
