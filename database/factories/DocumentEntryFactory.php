<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Enums\ProductType;
use App\Models\Oem;
use App\Models\Product;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

class DocumentEntryFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = DocumentEntry::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid,
            'oem_id'      => static function (): Oem {
                return Oem::factory()->create();
            },
            'document_id' => static function (): Document {
                return Document::factory()->create();
            },
            'asset_id'    => static function (): Asset {
                return Asset::factory()->create();
            },
            'product_id'  => static function (array $properties): Product {
                return Product::factory()->create([
                    'oem_id' => $properties['oem_id'],
                    'type'   => ProductType::service(),
                ]);
            },
            'quantity'    => $this->faker->randomDigit,
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
