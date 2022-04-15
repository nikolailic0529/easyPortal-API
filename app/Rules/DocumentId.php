<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Document;
use Illuminate\Contracts\Validation\Rule;

use function __;

class DocumentId implements Rule {
    /**
     * @inheritdoc
     */
    public function passes($attribute, $value): bool {
        return Document::query()->queryDocuments()->whereKey($value)->exists();
    }

    public function message(): string {
        return __('validation.document_id');
    }
}
