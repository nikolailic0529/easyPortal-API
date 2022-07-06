<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method Location create($attributes = [], ?Model $parent = null)
 * @method Location make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Location>
 */
class LocationFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Location::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid(),
            'postcode'        => $this->faker->postcode(),
            'state'           => $this->faker->state(),
            'line_one'        => $this->faker->streetAddress(),
            'line_two'        => $this->faker->secondaryAddress(),
            'country_id'      => static function (): Country {
                return Country::query()->first() ?? Country::factory()->create();
            },
            'city_id'         => static function (): City {
                return City::factory()->create();
            },
            'latitude'        => null,
            'longitude'       => null,
            'geohash'         => null,
            'assets_count'    => 0,
            'customers_count' => 0,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
