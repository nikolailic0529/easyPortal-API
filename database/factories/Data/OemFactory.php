<?php declare(strict_types = 1);

namespace Database\Factories\Data;

use App\Models\Data\Oem;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Oem create($attributes = [], ?Model $parent = null)
 * @method Oem make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Oem>
 */
class OemFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Oem::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'key'        => $this->faker->text(32),
            'name'       => $this->faker->company(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
