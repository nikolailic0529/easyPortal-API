<?php declare(strict_types = 1);

namespace App\Services\Settings\Types;

use App\Models\CustomerLocation;
use App\Models\Data\Type as TypeModel;
use App\Models\ResellerLocation;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class LocationType extends StringType {
    public function getValues(): Collection|array|null {
        return TypeModel::query()
            ->whereIn('object_type', [
                (new ResellerLocation())->getMorphClass(),
                (new CustomerLocation())->getMorphClass(),
            ])
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
