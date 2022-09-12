<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\ResellerLocation;
use App\Models\ResellerLocationType;
use App\Models\Data\Type;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method ResellerLocationType create($attributes = [], ?Model $parent = null)
 * @method ResellerLocationType make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<ResellerLocationType>
 */
class ResellerLocationTypeFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = ResellerLocationType::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'                   => $this->faker->uuid(),
            'type_id'              => Type::factory(),
            'reseller_location_id' => ResellerLocation::factory(),
            'created_at'           => Date::now(),
            'updated_at'           => Date::now(),
            'deleted_at'           => null,
        ];
    }
}
