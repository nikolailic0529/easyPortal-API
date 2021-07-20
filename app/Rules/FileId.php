<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\File;
use Illuminate\Contracts\Validation\Rule;

use function __;

class FileId implements Rule {

    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return File::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.file_id');
    }
}
