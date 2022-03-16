<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method CustomerLocation create($attributes = [], ?Model $parent = null)
 * @method CustomerLocation make($attributes = [], ?Model $parent = null)
 */
class CustomerLocationFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = CustomerLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'           => $this->faker->uuid,
            'customer_id'  => static function (): Customer {
                return Customer::factory()->create();
            },
            'location_id'  => static function (): Location {
                return Location::factory()->create();
            },
            'assets_count' => 0,
            'created_at'   => Date::now(),
            'updated_at'   => Date::now(),
            'deleted_at'   => null,
        ];
    }
}
