<?php declare(strict_types = 1);

namespace Database\Factories\Data;

use App\Models\Data\Oem;
use App\Models\Data\Product;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Product create($attributes = [], ?Model $parent = null)
 * @method Product make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Product>
 */
class ProductFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Product::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'oem_id'     => static function (): Oem {
                return Oem::factory()->create();
            },
            'sku'        => $this->faker->uuid(),
            'name'       => $this->faker->sentence(),
            'eol'        => Date::now()->addYear()->startOfDay(),
            'eos'        => Date::now()->addMonth()->startOfDay(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
