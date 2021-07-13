<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Reseller;
use App\Models\Type;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\Reseller create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\Reseller make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class ResellerFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Reseller::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        $object = $this->newModel()->getMorphClass();

        return [
            'id'              => $this->faker->uuid,
            'type_id'         => static function () use ($object): Type {
                return Type::factory()->create(['object_type' => $object]);
            },
            'name'            => $this->faker->company,
            'customers_count' => 0,
            'locations_count' => 0,
            'assets_count'    => 0,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
