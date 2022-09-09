<?php declare(strict_types = 1);

namespace Database\Factories\Data;

use App\Models\Data\Team;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method Team create($attributes = [], ?Model $parent = null)
 * @method Team make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Team>
 */
class TeamFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Team::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'name'       => $this->faker->word(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
