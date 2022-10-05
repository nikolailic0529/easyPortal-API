<?php declare(strict_types = 1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Data\ServiceLevel;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestAsset;
use App\Models\QuoteRequestDuration;
use App\Utils\Eloquent\Testing\Database\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;

/**
 * @method QuoteRequestAsset create($attributes = [], ?Model $parent = null)
 * @method QuoteRequestAsset make($attributes = [], ?Model $parent = null)
 *
 * @extends Factory<QuoteRequestAsset>
 */
class QuoteRequestAssetFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
     */
    protected $model = QuoteRequestAsset::class;

    /**
     * @inheritDoc
     */
    public function definition(): array {
        return [
            'id'                   => $this->faker->uuid(),
            'request_id'           => static function (): QuoteRequest {
                return QuoteRequest::query()->first() ?? QuoteRequest::factory()->create();
            },
            'service_level_id'     => static function (): ServiceLevel {
                return ServiceLevel::query()->first() ?? ServiceLevel::factory()->create();
            },
            'service_level_custom' => null,
            'asset_id'             => static function (): Asset {
                return Asset::query()->first() ?? Asset::factory()->create();
            },
            'duration_id'          => static function (): QuoteRequestDuration {
                return QuoteRequestDuration::query()->first() ?? QuoteRequestDuration::factory()->create();
            },
            'created_at'           => Date::now(),
            'updated_at'           => Date::now(),
            'deleted_at'           => null,
        ];
    }
}
