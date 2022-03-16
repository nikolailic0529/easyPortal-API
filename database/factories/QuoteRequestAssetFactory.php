<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestAsset;
use App\Models\QuoteRequestDuration;
use App\Models\ServiceLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Database\Eloquent\Factories\Factory;

/**
 * @method QuoteRequestAsset create($attributes = [], ?Model $parent = null)
 * @method QuoteRequestAsset make($attributes = [], ?Model $parent = null)
 */
class QuoteRequestAssetFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $model = QuoteRequestAsset::class;

    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array {
        return [
            'id'               => $this->faker->uuid,
            'request_id'       => static function (): QuoteRequest {
                return QuoteRequest::query()->first() ?? QuoteRequest::factory()->create();
            },
            'service_level_id' => static function (): ServiceLevel {
                return ServiceLevel::query()->first() ?? ServiceLevel::factory()->create();
            },
            'asset_id'         => static function (): Asset {
                return Asset::query()->first() ?? Asset::factory()->create();
            },
            'duration_id'      => static function (): QuoteRequestDuration {
                return QuoteRequestDuration::query()->first() ?? QuoteRequestDuration::factory()->create();
            },
            'created_at'       => Date::now(),
            'updated_at'       => Date::now(),
            'deleted_at'       => null,
        ];
    }
}
