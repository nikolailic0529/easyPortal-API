<?php declare(strict_types = 1);

namespace Database\Factories\Data;

use App\Models\Data\City;
use App\Models\Data\Country;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method City create($attributes = [], ?Model $parent = null)
 * @method City make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<City>
 */
class CityFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = City::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'key'        => $this->faker->uuid(),
            'name'       => $this->faker->city(),
            'country_id' => Country::factory(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
