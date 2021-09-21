<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Type;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

use function array_keys;

/**
 * @method \App\Models\Type create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\Type make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class TypeFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Type::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid,
            'object_type' => $this->faker->randomElement(array_keys(Relation::$morphMap)),
            'key'         => $this->faker->uuid,
            'name'        => $this->faker->sentence,
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
