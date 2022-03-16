<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method ResellerCustomer create($attributes = [], ?Model $parent = null)
 * @method ResellerCustomer make($attributes = [], ?Model $parent = null)
 */
class ResellerCustomerFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = ResellerCustomer::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid,
            'reseller_id'     => static function (): Reseller {
                return Reseller::factory()->create();
            },
            'customer_id'     => static function (): Customer {
                return Customer::factory()->create();
            },
            'kpi_id'          => null,
            'assets_count'    => 0,
            'locations_count' => 0,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
