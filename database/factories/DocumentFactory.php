<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Data\Oem;
use App\Models\Data\Type;
use App\Models\Document;
use App\Models\Reseller;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

use function number_format;

/**
 * @method Document create($attributes = [], ?Model $parent = null)
 * @method Document make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = Document::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'             => $this->faker->uuid(),
            'oem_id'         => Oem::factory(),
            'oem_said'       => $this->faker->randomElement([null, $this->faker->uuid()]),
            'oem_group_id'   => null,
            'type_id'        => function (): Type {
                return Type::factory()->create([
                    'object_type' => $this->newModel()->getMorphClass(),
                ]);
            },
            'reseller_id'    => Reseller::factory(),
            'customer_id'    => Customer::factory(),
            'entries_count'  => 0,
            'contacts_count' => 0,
            'statuses_count' => 0,
            'number'         => $this->faker->uuid(),
            'start'          => $this->faker->dateTime(),
            'end'            => $this->faker->dateTime(),
            'price_origin'   => number_format($this->faker->randomFloat(2), 2, '.', ''),
            'price'          => static function (array $attributes): mixed {
                return $attributes['price_origin'] ?? null;
            },
            'currency_id'    => null,
            'language_id'    => null,
            'oem_amp_id'     => null,
            'oem_sar_number' => null,
            'changed_at'     => null,
            'synced_at'      => Date::now(),
            'created_at'     => Date::now(),
            'updated_at'     => Date::now(),
            'deleted_at'     => null,
        ];
    }
}
