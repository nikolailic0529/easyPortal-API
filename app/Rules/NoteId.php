<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Note;
use Illuminate\Contracts\Validation\Rule;

use function __;

class NoteId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $value && Note::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.note_id');
    }
}
