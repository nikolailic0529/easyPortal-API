<?php declare(strict_types = 1);

namespace Database\Factories\Data;

use App\Models\Data\Currency;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Currency::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'code'       => $this->faker->currencyCode(),
            'name'       => $this->faker->currencyCode(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
