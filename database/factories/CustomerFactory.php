<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method Customer create($attributes = [], ?Model $parent = null)
 * @method Customer make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid(),
            'name'            => $this->faker->company(),
            'kpi_id'          => null,
            'assets_count'    => 0,
            'quotes_count'    => 0,
            'contracts_count' => 0,
            'locations_count' => 0,
            'contacts_count'  => 0,
            'statuses_count'  => 0,
            'changed_at'      => null,
            'synced_at'       => Date::now(),
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
