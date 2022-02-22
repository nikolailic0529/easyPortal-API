<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Testing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use function in_array;
use function str_ends_with;

/**
 * @mixin \Tests\TestCase
 */
trait Helper {
    /**
     * @param \Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model>
     *     |\Illuminate\Database\Eloquent\Model $model
     * @param array<string> $attributes
     *
     * @return array<string, mixed>
     */
    protected function getModelCountableProperties(Collection|Model $model, array $attributes = []): array {
        $properties = [];

        if ($model instanceof Collection) {
            $properties = $model
                ->map(function (Model $model) use ($attributes): array {
                    return $this->getModelCountableProperties($model, $attributes);
                })
                ->all();
        } else {
            foreach ($model->getAttributes() as $attribute => $value) {
                if (str_ends_with($attribute, '_count') || in_array($attribute, $attributes, true)) {
                    $properties[$attribute] = $value;
                }
            }
        }

        return $properties;
    }
}
