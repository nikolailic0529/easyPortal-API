<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Enums\ProductType;
use App\Models\Product;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\DocumentEntry create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\DocumentEntry make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
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
            'document_id' => static function (): Document {
                return Document::factory()->create();
            },
            'asset_id'    => static function (): Asset {
                return Asset::factory()->create();
            },
            'product_id'  => static function (): Product {
                return Product::factory()->create([
                    'type' => ProductType::service(),
                ]);
            },
            'quantity'    => $this->faker->randomDigit,
            'currency_id' => null,
            'net_price'   => null,
            'list_price'  => null,
            'discount'    => null,
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
