<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Psp;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Psp create($attributes = [], ?Model $parent = null)
 * @method Psp make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Psp>
 */
class PspFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Psp::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'key'        => $this->faker->uuid(),
            'name'       => $this->faker->sentence(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
