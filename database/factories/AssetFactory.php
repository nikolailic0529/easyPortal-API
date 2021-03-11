<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'            => $this->faker->uuid,
            'ogranization'  => static function (): Organization {
                return Organization::factory()->create();
            },
            'oem_id'        => static function (): Oem {
                return Oem::factory()->create();
            },
            'type_id'       => function (): Type {
                return Type::factory()->create([
                    'object_type' => $this->newModel()->getMorphClass(),
                ]);
            },
            'product_id'    => static function (array $properties): Product {
                return Product::factory()->create(['oem_id' => $properties['oem_id']]);
            },
            'customer_id'   => static function (): Customer {
                return Customer::factory()->create();
            },
            'location_id'   => static function (array $properties): Location {
                return Location::factory()->create([
                    'object_type' => (new Customer())->getMorphClass(),
                    'object_id'   => $properties['customer_id'],
                ]);
            },
            'serial_number' => $this->faker->uuid,
            'created_at'    => Date::now(),
            'updated_at'    => Date::now(),
            'deleted_at'    => null,
        ];
    }
}
