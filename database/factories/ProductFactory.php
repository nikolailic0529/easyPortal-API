<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Enums\ProductType;
use App\Models\Oem;
use App\Models\Product;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\Product create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\Product make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
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
        return [
            'id'         => $this->faker->uuid,
            'type'       => $this->faker->randomElement(ProductType::values()),
            'oem_id'     => static function (): Oem {
                return Oem::factory()->create();
            },
            'sku'        => $this->faker->uuid,
            'name'       => $this->faker->sentence,
            'eol'        => Date::now()->addYear()->startOfDay(),
            'eos'        => Date::now()->addMonth()->startOfDay(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
