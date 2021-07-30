<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Contact;
use Illuminate\Contracts\Validation\Rule;

use function __;

class ContactId implements Rule {

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return Contact::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.contact_id');
    }
}
