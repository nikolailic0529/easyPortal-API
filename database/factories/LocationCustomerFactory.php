<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Data\Location;
use App\Models\LocationCustomer;
use App\Models\Customer;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method LocationCustomer create($attributes = [], ?Model $parent = null)
 * @method LocationCustomer make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<LocationCustomer>
 */
class LocationCustomerFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = LocationCustomer::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid(),
            'location_id' => Location::factory(),
            'customer_id' => Customer::factory(),
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
