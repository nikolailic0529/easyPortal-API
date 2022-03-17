<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Location;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method ResellerLocation create($attributes = [], ?Model $parent = null)
 * @method ResellerLocation make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<ResellerLocation>
 */
class ResellerLocationFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = ResellerLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'              => $this->faker->uuid,
            'reseller_id'     => static function (): Reseller {
                return Reseller::factory()->create();
            },
            'location_id'     => static function (): Location {
                return Location::factory()->create();
            },
            'assets_count'    => 0,
            'customers_count' => 0,
            'created_at'      => Date::now(),
            'updated_at'      => Date::now(),
            'deleted_at'      => null,
        ];
    }
}
