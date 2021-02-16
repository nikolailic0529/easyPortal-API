<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

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
            'id'                => Str::uuid()->toString(),
            'organization_id'   => static function (): Organization {
                return Organization::query()->first() ?: Organization::factory()->create();
            },
            'given_name'        => $this->faker->firstName,
            'family_name'       => $this->faker->lastName,
            'email'             => $this->faker->unique()->safeEmail,
            'email_verified_at' => Date::now(),
            'phone'             => $this->faker->e164PhoneNumber,
            'phone_verified_at' => null,
            'photo'             => null,
            'created_at'        => Date::now(),
            'updated_at'        => Date::now(),
            'permissions'       => [],
        ];
    }
}
