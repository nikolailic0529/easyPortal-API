<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method Currency create($attributes = [], ?Model $parent = null)
 * @method Currency make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid,
            'code'       => $this->faker->currencyCode,
            'name'       => $this->faker->currencyCode,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
