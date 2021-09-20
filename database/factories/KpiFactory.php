<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Kpi;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\Kpi create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\Kpi make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
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
            'id'                      => $this->faker->uuid,
            'object_id'               => $this->faker->uuid,
            'object_type'             => $this->faker->word,
            'quotes_expiring'         => 0,
            'quotes_active_new'       => 0,
            'quotes_active_amount'    => 0.0,
            'quotes_active'           => 0,
            'contracts_expiring'      => 0,
            'contracts_active_new'    => 0,
            'contracts_active_amount' => 0.0,
            'contracts_active'        => 0,
            'customers_active_new'    => 0,
            'customers_active'        => 0,
            'assets_covered'          => 0.0,
            'assets_active'           => 0,
            'assets_total'            => 0,
            'created_at'              => Date::now(),
            'updated_at'              => Date::now(),
            'deleted_at'              => null,
        ];
    }
}
