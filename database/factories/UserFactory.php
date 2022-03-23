<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method User create($attributes = [], ?Model $parent = null)
 * @method User make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = User::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid(),
            'type'            => UserType::keycloak(),
            'organization_id' => null,
            'given_name'      => $this->faker->firstName(),
            'family_name'     => $this->faker->lastName(),
            'email'           => $this->faker->unique()->safeEmail(),
            'email_verified'  => true,
            'phone'           => $this->faker->e164PhoneNumber(),
            'phone_verified'  => false,
            'photo'           => null,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
            'permissions'     => [],
            'locale'          => null,
            'password'        => null,
            'homepage'        => null,
            'timezone'        => null,
            'office_phone'    => null,
            'contact_email'   => null,
            'title'           => null,
            'academic_title'  => null,
            'mobile_phone'    => null,
            'job_title'       => null,
            'company'         => null,
            'enabled'         => true,
            'synced_at'       => null,
        ];
    }
}
