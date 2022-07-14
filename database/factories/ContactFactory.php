<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Contact;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;

use function array_keys;

/**
 * @method Contact create($attributes = [], ?Model $parent = null)
 * @method Contact make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Contact::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'           => $this->faker->uuid(),
            'object_id'    => $this->faker->uuid(),
            'object_type'  => $this->faker->randomElement(array_keys(Relation::$morphMap)),
            'name'         => $this->faker->name(),
            'email'        => $this->faker->email(),
            'phone_number' => $this->faker->e164PhoneNumber(),
            'phone_valid'  => false,
            'created_at'   => Date::now(),
            'updated_at'   => Date::now(),
            'deleted_at'   => null,
        ];
    }
}
