<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

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
            'type'              => 'reseller',
            'given_name'        => $this->faker->firstName,
            'family_name'       => $this->faker->lastName,
            'email'             => $this->faker->unique()->safeEmail,
            'email_verified_at' => Date::now(),
            'phone'             => null,
            'phone_verified_at' => null,
            'photo'             => null,
            'organization_id'   => null,
            'customer_id'       => null,
            'created_at'        => Date::now(),
            'updated_at'        => Date::now(),
        ];
    }
}
