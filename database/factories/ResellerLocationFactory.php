<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Data\Location;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method ResellerLocation create($attributes = [], ?Model $parent = null)
 * @method ResellerLocation make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<ResellerLocation>
 */
class ResellerLocationFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = ResellerLocation::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid(),
            'reseller_id'     => Reseller::factory(),
            'location_id'     => Location::factory(),
            'assets_count'    => 0,
            'customers_count' => 0,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
