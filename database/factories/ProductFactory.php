<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Oem;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        $object = $this->newModel()->getMorphClass();

        return [
            'id'         => $this->faker->uuid,
            'oem_id'     => static function (): Oem {
                return Oem::query()->first()
                    ?: Oem::factory()->create();
            },
            'type_id'    => static function () use ($object): Type {
                return Type::query()->where('object_type', '=', $object)->first()
                    ?: Type::factory()->create(['object_type' => $object]);
            },
            'sku'        => $this->faker->uuid,
            'name'       => $this->faker->sentence,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
