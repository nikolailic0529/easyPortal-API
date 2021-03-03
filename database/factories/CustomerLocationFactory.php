<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Location;
use App\Models\Type;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

class CustomerLocationFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = CustomerLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        $object = $this->newModel()->getMorphClass();

        return [
            'type_id'     => static function () use ($object): Type {
                return Type::query()->where('object_type', '=', $object)->first()
                    ?: Type::factory()->create(['object_type' => $object]);
            },
            'customer_id' => static function (): Customer {
                return Customer::query()->first()
                    ?: Customer::factory()->create();
            },
            'location_id' => static function (): Location {
                return Location::query()->first()
                    ?: Location::factory()->create();
            },
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
