<?php declare(strict_types = 1);

namespace Database\Factories\Logs;

use App\Models\Logs\AnalyzeAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method AnalyzeAsset create($attributes = [], ?Model $parent = null)
 * @method AnalyzeAsset make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<AnalyzeAsset>
 */
class AnalyzeAssetFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = AnalyzeAsset::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'               => $this->faker->uuid,
            'unknown'          => null,
            'reseller_null'    => null,
            'reseller_types'   => null,
            'reseller_unknown' => null,
            'customer_null'    => null,
            'customer_types'   => null,
            'customer_unknown' => null,
            'created_at'       => Date::now(),
            'updated_at'       => Date::now(),
        ];
    }
}
