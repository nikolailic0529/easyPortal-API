<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Field;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;

use function array_keys;

/**
 * @method Field create($attributes = [], ?Model $parent = null)
 * @method Field make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Field>
 */
class FieldFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Field::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid(),
            'object_type' => $this->faker->randomElement(array_keys(Relation::$morphMap)),
            'key'         => $this->faker->uuid(),
            'name'        => $this->faker->sentence(),
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
