<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Data\Product;
use App\Models\Document;
use App\Models\DocumentEntry;
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
            'id'                          => $this->faker->uuid(),
            'key'                         => null,
            'document_id'                 => Document::factory(),
            'asset_id'                    => Asset::factory(),
            'asset_type_id'               => null,
            'product_id'                  => Product::factory(),
            'product_line_id'             => null,
            'product_group_id'            => null,
            'service_group_id'            => null,
            'service_level_id'            => null,
            'currency_id'                 => null,
            'start'                       => $this->faker->dateTime(),
            'end'                         => $this->faker->dateTime(),
            'list_price_origin'           => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'list_price'                  => static function (array $attributes): mixed {
                return $attributes['list_price_origin'] ?? null;
            },
            'monthly_list_price_origin'   => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'monthly_list_price'          => static function (array $attributes): mixed {
                return $attributes['monthly_list_price_origin'] ?? null;
            },
            'monthly_retail_price_origin' => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'monthly_retail_price'        => static function (array $attributes): mixed {
                return $attributes['monthly_retail_price_origin'] ?? null;
            },
            'renewal_origin'              => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'renewal'                     => static function (array $attributes): mixed {
                return $attributes['renewal_origin'] ?? null;
            },
            'oem_said'                    => null,
            'oem_sar_number'              => null,
            'environment_id'              => null,
            'equipment_number'            => $this->faker->uuid(),
            'language_id'                 => null,
            'removed_at'                  => null,
            'created_at'                  => Date::now(),
            'updated_at'                  => Date::now(),
            'deleted_at'                  => null,
        ];
    }
}
