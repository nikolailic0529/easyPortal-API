<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\File;
use Illuminate\Contracts\Validation\Rule;

use function trans;

class FileId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return $value && File::query()->whereKey($value)->exists();
    }

    public function message(): string {
        return trans('validation.file_id');
    }
}
