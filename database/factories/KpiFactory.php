<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Kpi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method Kpi create($attributes = [], ?Model $parent = null)
 * @method Kpi make($attributes = [], ?Model $parent = null)
 */
class KpiFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Kpi::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'                                  => $this->faker->uuid,
            'assets_total'                        => 0,
            'assets_active'                       => 0,
            'assets_active_percent'               => 0.0,
            'assets_active_on_contract'           => 0,
            'assets_active_on_warranty'           => 0,
            'assets_active_exposed'               => 0,
            'customers_active'                    => 0,
            'customers_active_new'                => 0,
            'contracts_active'                    => 0,
            'contracts_active_amount'             => 0.0,
            'contracts_active_new'                => 0,
            'contracts_expiring'                  => 0,
            'contracts_expired'                   => 0,
            'quotes_active'                       => 0,
            'quotes_active_amount'                => 0.0,
            'quotes_active_new'                   => 0,
            'quotes_expiring'                     => 0,
            'quotes_expired'                      => 0,
            'quotes_ordered'                      => 0,
            'quotes_accepted'                     => 0,
            'quotes_requested'                    => 0,
            'quotes_received'                     => 0,
            'quotes_rejected'                     => 0,
            'quotes_awaiting'                     => 0,
            'service_revenue_total_amount'        => 0.0,
            'service_revenue_total_amount_change' => 0.0,
            'created_at'                          => Date::now(),
            'updated_at'                          => Date::now(),
            'deleted_at'                          => null,
        ];
    }
}
