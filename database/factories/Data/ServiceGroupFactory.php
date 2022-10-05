<?php declare(strict_types = 1);

namespace Database\Factories\Data;

use App\Models\Data\Oem;
use App\Models\Data\ServiceGroup;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method ServiceGroup create($attributes = [], ?Model $parent = null)
 * @method ServiceGroup make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<ServiceGroup>
 */
class ServiceGroupFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = ServiceGroup::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'oem_id'     => static function (): Oem {
                return Oem::factory()->create();
            },
            'key'        => $this->faker->uuid(),
            'sku'        => $this->faker->uuid(),
            'name'       => $this->faker->sentence(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
