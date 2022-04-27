<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSearch;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

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
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = UserSearch::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'name'       => $this->faker->word(),
            'key'        => $this->faker->word(),
            'conditions' => $this->faker->word(),
            'user_id'    => static function (): User {
                return User::factory()->create();
            },
        ];
    }
}
