<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\User;
use App\Services\Organization\CurrentOrganization;
use Illuminate\Contracts\Validation\Rule;

use function __;
use function app;

class OrgUserId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        $organization = app()->make(CurrentOrganization::class)->get();

        return $organization
            ->users()
            ->where((new User())->getQualifiedKeyName(), '=', $value)
            ->exists();
    }

    public function message(): string {
        return __('validation.org_user_id');
    }
}
