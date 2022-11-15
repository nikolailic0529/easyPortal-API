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
            'request_id'           => QuoteRequest::factory(),
            'service_level_id'     => ServiceLevel::factory(),
            'service_level_custom' => null,
            'asset_id'             => Asset::factory(),
            'duration_id'          => QuoteRequestDuration::factory(),
            'created_at'           => Date::now(),
            'updated_at'           => Date::now(),
            'deleted_at'           => null,
        ];
    }
}
