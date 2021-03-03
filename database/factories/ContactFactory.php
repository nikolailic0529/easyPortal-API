<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Contact::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'           => $this->faker->uuid,
            'object_id'    => $this->faker->uuid,
            'object_type'  => $this->faker->uuid,
            'name'         => $this->faker->name,
            'email'        => $this->faker->email,
            'phone_number' => $this->faker->e164PhoneNumber,
            'phone_valid'  => false,
            'created_at'   => Date::now(),
            'updated_at'   => Date::now(),
            'deleted_at'   => null,
        ];
    }
}
