<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Status;
use App\Models\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method Asset create($attributes = [], ?Model $parent = null)
 * @method Asset make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Asset>
 */
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
            'id'                  => $this->faker->uuid,
            'reseller_id'         => static function (): Reseller {
                return Reseller::factory()->create();
            },
            'oem_id'              => static function (): Oem {
                return Oem::factory()->create();
            },
            'type_id'             => function (): Type {
                return Type::factory()->create([
                    'object_type' => $this->newModel()->getMorphClass(),
                ]);
            },
            'product_id'          => static function (array $properties): Product {
                return Product::factory()->create([
                    'oem_id' => $properties['oem_id'],
                ]);
            },
            'customer_id'         => static function (): Customer {
                return Customer::factory()->create();
            },
            'location_id'         => static function (array $properties): Location {
                return Location::factory()->create();
            },
            'status_id'           => function (): Status {
                return Status::factory()->create([
                    'object_type' => $this->newModel()->getMorphClass(),
                ]);
            },
            'serial_number'       => $this->faker->uuid,
            'warranty_end'        => null,
            'warranty_changed_at' => null,
            'contacts_count'      => 0,
            'coverages_count'     => 0,
            'data_quality'        => null,
            'changed_at'          => null,
            'synced_at'           => Date::now(),
            'created_at'          => Date::now(),
            'updated_at'          => Date::now(),
            'deleted_at'          => null,
        ];
    }
}
