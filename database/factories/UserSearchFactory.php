<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSearch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\User create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\User make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
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
            'name'       => $this->faker->word,
            'key'        => $this->faker->word,
            'conditions' => $this->faker->word,
            'user_id'    => static function (): User {
                return User::factory()->create();
            },
        ];
    }
}
