<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use App\Models\Document;
use App\Models\Type as TypeModel;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class DocumentType extends StringType {
    public function getValues(): Collection|array|null {
        return TypeModel::query()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->orderByKey()
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        $ids = $this->getValues()
            ->map(static function (TypeModel $type): string {
                return $type->getKey();
            })
            ->all();

        return [Rule::in($ids)];
    }
}
