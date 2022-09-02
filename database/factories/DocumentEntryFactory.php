<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Currency;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Product;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

use function number_format;

/**
 * @method DocumentEntry create($attributes = [], ?Model $parent = null)
 * @method DocumentEntry make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<DocumentEntry>
 */
class DocumentEntryFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = DocumentEntry::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'                   => $this->faker->uuid(),
            'document_id'          => static function (): Document {
                return Document::factory()->create();
            },
            'asset_id'             => static function (): Asset {
                return Asset::factory()->create();
            },
            'asset_type_id'        => null,
            'product_id'           => static function (): Product {
                return Product::factory()->create();
            },
            'service_group_id'     => null,
            'service_level_id'     => null,
            'currency_id'          => static function (): Currency {
                return Currency::query()->first() ?? Currency::factory()->create();
            },
            'start'                => $this->faker->dateTime(),
            'end'                  => $this->faker->dateTime(),
            'list_price'           => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'monthly_list_price'   => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'monthly_retail_price' => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'renewal'              => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'oem_said'             => null,
            'oem_sar_number'       => null,
            'environment_id'       => null,
            'equipment_number'     => $this->faker->uuid(),
            'language_id'          => null,
            'created_at'           => Date::now(),
            'updated_at'           => Date::now(),
            'deleted_at'           => null,
        ];
    }
}
