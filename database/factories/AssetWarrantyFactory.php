<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Reseller;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method \App\Models\AssetWarranty create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 * @method \App\Models\AssetWarranty make($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
 */
class AssetWarrantyFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = AssetWarranty::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'          => $this->faker->uuid,
            'asset_id'    => static function (): Asset {
                return Asset::factory()->create();
            },
            'document_id' => null,
            'support_id'  => null,
            'reseller_id' => static function (): Reseller {
                return Reseller::factory()->create();
            },
            'customer_id' => static function (): Customer {
                return Customer::factory()->create();
            },
            'start'       => $this->faker->randomElement([null, $this->faker->dateTime]),
            'end'         => $this->faker->randomElement([null, $this->faker->dateTime]),
            'note'        => null,
            'created_at'  => Date::now(),
            'updated_at'  => Date::now(),
            'deleted_at'  => null,
        ];
    }
}
