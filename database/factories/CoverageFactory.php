<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Coverage;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Coverage create($attributes = [], ?Model $parent = null)
 * @method Coverage make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Coverage>
 */
class CoverageFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Coverage::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'name'       => $this->faker->word(),
            'key'        => $this->faker->word(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
