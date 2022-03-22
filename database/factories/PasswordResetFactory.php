<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\PasswordReset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method PasswordReset create($attributes = [], ?Model $parent = null)
 * @method PasswordReset make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<PasswordReset>
 */
class PasswordResetFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = PasswordReset::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid,
            'email'      => $this->faker->email,
            'token'      => $this->faker->uuid,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
