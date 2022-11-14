<?php declare(strict_types = 1);

namespace Database\Factories\Data;

use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method ServiceLevel create($attributes = [], ?Model $parent = null)
 * @method ServiceLevel make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<ServiceLevel>
 */
class ServiceLevelFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = ServiceLevel::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'               => $this->faker->uuid(),
            'oem_id'           => Oem::factory(),
            'service_group_id' => ServiceGroup::factory(),
            'key'              => $this->faker->uuid(),
            'sku'              => $this->faker->uuid(),
            'name'             => $this->faker->sentence(),
            'description'      => $this->faker->text(),
            'created_at'       => Date::now(),
            'updated_at'       => Date::now(),
            'deleted_at'       => null,
        ];
    }
}
