<?php declare(strict_types = 1);

namespace Database\Factories\Data;

use App\Models\Data\ProductGroup;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method ProductGroup create($attributes = [], ?Model $parent = null)
 * @method ProductGroup make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<ProductGroup>
 */
class ProductGroupFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = ProductGroup::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'key'        => $this->faker->uuid(),
            'name'       => $this->faker->word(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
