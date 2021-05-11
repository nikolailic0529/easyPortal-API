<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\User create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\User make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class UserFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid,
            'type'            => UserType::keycloak(),
            'organization_id' => null,
            'given_name'      => $this->faker->firstName,
            'family_name'     => $this->faker->lastName,
            'email'           => $this->faker->unique()->safeEmail,
            'email_verified'  => true,
            'phone'           => $this->faker->e164PhoneNumber,
            'phone_verified'  => false,
            'photo'           => null,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
            'permissions'     => [],
            'locale'          => null,
        ];
    }
}
