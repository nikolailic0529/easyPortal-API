<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use App\Models\Document;
use App\Models\Status;
use App\Utils\Eloquent\Callbacks\GetKey;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class DocumentStatus extends StringType {
    /**
     * @return Collection<int, Status>
     */
    public function getValues(): Collection {
        return Status::query()
            ->where('object_type', '=', (new Document())->getMorphClass())
            ->orderByKey()
            ->get();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRules(): array {
        $ids   = $this->getValues()->map(new GetKey())->all();
        $rules = [Rule::in($ids)];

        return $rules;
    }
}
