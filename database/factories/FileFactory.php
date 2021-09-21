<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

use function array_keys;

/**
 * @method \App\Models\File create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\File make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class FileFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = File::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid,
            'object_id'   => $this->faker->uuid,
            'object_type' => $this->faker->randomElement(array_keys(Relation::$morphMap)),
            'name'        => $this->faker->word,
            'disk'        => $this->faker->word,
            'path'        => $this->faker->word,
            'type'        => $this->faker->mimeType,
            'size'        => $this->faker->randomNumber(5, false),
            'hash'        => $this->faker->sha256,
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
