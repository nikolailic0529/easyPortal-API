<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\QuoteRequestDuration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method QuoteRequestDuration create($attributes = [], ?Model $parent = null)
 * @method QuoteRequestDuration make($attributes = [], ?Model $parent = null)
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
