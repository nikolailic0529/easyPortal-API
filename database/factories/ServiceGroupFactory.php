<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Oem;
use App\Models\ServiceGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = ServiceGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid,
            'oem_id'     => static function (): Oem {
                return Oem::factory()->create();
            },
            'key'        => $this->faker->uuid,
            'sku'        => $this->faker->uuid,
            'name'       => $this->faker->sentence,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
