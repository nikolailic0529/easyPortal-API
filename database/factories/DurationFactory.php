<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Duration;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\Duration create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\Duration make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class DurationFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Duration::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid,
            'duration'   => $this->faker->word,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
