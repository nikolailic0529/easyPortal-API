<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Status;
use App\Models\Type;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        $object = $this->newModel()->getMorphClass();

        return [
            'id'         => $this->faker->uuid,
            'name'       => $this->faker->company,
            'type_id'    => static function () use ($object): Type {
                return Type::factory()->create(['object_type' => $object]);
            },
            'status_id'  => static function () use ($object): Status {
                return Status::factory()->create(['object_type' => $object]);
            },
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'deleted_at' => null,
        ];
    }
}
