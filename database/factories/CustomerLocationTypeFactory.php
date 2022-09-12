<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\CustomerLocation;
use App\Models\CustomerLocationType;
use App\Models\Data\Type;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method CustomerLocationType create($attributes = [], ?Model $parent = null)
 * @method CustomerLocationType make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<CustomerLocationType>
 */
class CustomerLocationTypeFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = CustomerLocationType::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'                   => $this->faker->uuid(),
            'type_id'              => Type::factory(),
            'customer_location_id' => CustomerLocation::factory(),
            'created_at'           => Date::now(),
            'updated_at'           => Date::now(),
            'deleted_at'           => null,
        ];
    }
}
