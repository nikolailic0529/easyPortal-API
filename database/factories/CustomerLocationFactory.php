<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Data\Location;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method CustomerLocation create($attributes = [], ?Model $parent = null)
 * @method CustomerLocation make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<CustomerLocation>
 */
class CustomerLocationFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = CustomerLocation::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'           => $this->faker->uuid(),
            'customer_id'  => Customer::factory(),
            'location_id'  => Location::factory(),
            'assets_count' => 0,
            'created_at'   => Date::now(),
            'updated_at'   => Date::now(),
            'deleted_at'   => null,
        ];
    }
}
