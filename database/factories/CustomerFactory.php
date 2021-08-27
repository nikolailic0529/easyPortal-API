<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Type;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\Customer create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\Customer make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
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
        $object = $this->newModel()->getMorphClass();

        return [
            'id'                          => $this->faker->uuid,
            'name'                        => $this->faker->company,
            'type_id'                     => static function () use ($object): Type {
                return Type::factory()->create(['object_type' => $object]);
            },
            'assets_count'                => 0,
            'locations_count'             => 0,
            'contacts_count'              => 0,
            'kpi_quotes_expiring'         => 0,
            'kpi_quotes_active_new'       => 0,
            'kpi_quotes_active_amount'    => 0.0,
            'kpi_quotes_active'           => 0,
            'kpi_contracts_expiring'      => 0,
            'kpi_contracts_active_new'    => 0,
            'kpi_contracts_active_amount' => 0.0,
            'kpi_contracts_active'        => 0,
            'kpi_customers_active_new'    => 0,
            'kpi_customers_active'        => 0,
            'kpi_assets_covered'          => 0.0,
            'kpi_assets_active'           => 0,
            'kpi_assets_total'            => 0,
            'changed_at'                  => null,
            'created_at'                  => Date::now(),
            'updated_at'                  => Date::now(),
            'deleted_at'                  => null,
        ];
    }
}
