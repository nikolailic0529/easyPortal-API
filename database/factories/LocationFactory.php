<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid,
            'postcode'   => $this->faker->postcode,
            'state'      => $this->faker->state,
            'line_one'   => $this->faker->streetAddress,
            'line_two'   => $this->faker->secondaryAddress,
            'country_id' => static function (): Country {
                return Country::query()->first()
                    ?: Country::factory()->create();
            },
            'city_id'    => static function (): City {
                return City::query()->first()
                    ?: City::factory()->create();
            },
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
