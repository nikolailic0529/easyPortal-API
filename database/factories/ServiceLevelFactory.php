<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Oem;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\ServiceLevel create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\ServiceLevel make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class ServiceLevelFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = ServiceLevel::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'               => $this->faker->uuid,
            'oem_id'           => static function (): Oem {
                return Oem::factory()->create();
            },
            'service_group_id' => static function (): ServiceGroup {
                return ServiceGroup::factory()->create();
            },
            'key'              => $this->faker->uuid,
            'sku'              => $this->faker->uuid,
            'name'             => $this->faker->sentence,
            'description'      => $this->faker->text,
            'created_at'       => Date::now(),
            'updated_at'       => Date::now(),
            'deleted_at'       => null,
        ];
    }
}
