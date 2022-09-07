<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\ProductLine;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method ProductLine create($attributes = [], ?Model $parent = null)
 * @method ProductLine make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<ProductLine>
 */
class ProductLineFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = ProductLine::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'key'        => $this->faker->uuid(),
            'name'       => $this->faker->sentence(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
