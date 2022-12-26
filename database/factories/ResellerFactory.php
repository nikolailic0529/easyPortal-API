<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Reseller;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Reseller create($attributes = [], ?Model $parent = null)
 * @method Reseller make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Reseller>
 */
class ResellerFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Reseller::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid(),
            'kpi_id'          => null,
            'name'            => $this->faker->company(),
            'customers_count' => 0,
            'locations_count' => 0,
            'assets_count'    => 0,
            'statuses_count'  => 0,
            'changed_at'      => null,
            'synced_at'       => null,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
