<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\QuoteRequestDuration;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\QuoteRequestDuration
 *  create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\QuoteRequestDuration make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class QuoteRequestDurationFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = QuoteRequestDuration::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid,
            'name'       => $this->faker->word,
            'key'        => $this->faker->word,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
