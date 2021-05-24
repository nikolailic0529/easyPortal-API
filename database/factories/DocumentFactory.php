<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Oem;
use App\Models\Reseller;
use App\Models\Type;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\Document create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\Document make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class DocumentFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'             => $this->faker->uuid,
            'oem_id'         => static function (): Oem {
                return Oem::factory()->create();
            },
            'type_id'        => function (): Type {
                return Type::factory()->create([
                    'object_type' => $this->newModel()->getMorphClass(),
                ]);
            },
            'reseller_id'    => static function (): Reseller {
                return Reseller::factory()->create();
            },
            'customer_id'    => static function (): Customer {
                return Customer::factory()->create();
            },
            'entries_count'  => 0,
            'contacts_count' => 0,
            'support_id'     => null,
            'number'         => $this->faker->uuid,
            'start'          => $this->faker->dateTime,
            'end'            => $this->faker->dateTime,
            'price'          => (string) $this->faker->randomFloat(2),
            'currency_id'    => null,
            'created_at'     => Date::now(),
            'updated_at'     => Date::now(),
            'deleted_at'     => null,
        ];
    }
}
