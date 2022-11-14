<?php declare(strict_types = 1);

namespace Database\Factories\Data;

use App\Models\Data\Country;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Country create($attributes = [], ?Model $parent = null)
 * @method Country make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Country>
 */
class CountryFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Country::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'code'       => $this->faker->unique()->countryCode(),
            'name'       => $this->faker->country(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
