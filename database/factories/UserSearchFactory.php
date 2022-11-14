<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSearch;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method UserSearch create($attributes = [], ?Model $parent = null)
 * @method UserSearch make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<UserSearch>
 */
class UserSearchFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = UserSearch::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'name'       => $this->faker->word(),
            'key'        => $this->faker->uuid(),
            'conditions' => $this->faker->word(),
            'user_id'    => User::factory(),
        ];
    }
}
