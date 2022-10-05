<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerStatus;
use App\Models\Data\Status;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method CustomerStatus create($attributes = [], ?Model $parent = null)
 * @method CustomerStatus make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<CustomerStatus>
 */
class CustomerStatusFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = CustomerStatus::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid(),
            'customer_id' => Customer::factory(),
            'status_id'   => Status::factory(),
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
