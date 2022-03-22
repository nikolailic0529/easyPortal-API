<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = City::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid,
            'key'        => $this->faker->uuid,
            'name'       => $this->faker->city,
            'country_id' => static function (): Country {
                return Country::query()->first() ?? Country::factory()->create();
            },
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
