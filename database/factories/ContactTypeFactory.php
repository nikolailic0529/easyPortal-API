<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactType;
use App\Models\Data\Type;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method ContactType create($attributes = [], ?Model $parent = null)
 * @method ContactType make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<ContactType>
 */
class ContactTypeFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = ContactType::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'         => $this->faker->uuid(),
            'contact_id' => Contact::factory(),
            'type_id'    => Type::factory(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
