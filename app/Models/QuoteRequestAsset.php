<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Data\ServiceLevel;
use App\Models\Relations\HasAsset;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\QuoteRequestAssetFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QuoteRequestAsset.
 *
 * @property string                    $id
 * @property string                    $request_id
 * @property string                    $asset_id
 * @property string                    $duration_id
 * @property string|null               $service_level_id
 * @property string|null               $service_level_custom
 * @property CarbonImmutable           $created_at
 * @property CarbonImmutable           $updated_at
 * @property CarbonImmutable|null      $deleted_at
 * @property Asset                     $asset
 * @property-read QuoteRequestDuration $duration
 * @property-read ServiceLevel|null    $serviceLevel
 * @property-read QuoteRequest         $request
 * @method static QuoteRequestAssetFactory factory(...$parameters)
 * @method static Builder|QuoteRequestAsset newModelQuery()
 * @method static Builder|QuoteRequestAsset newQuery()
 * @method static Builder|QuoteRequestAsset query()
 */
class QuoteRequestAsset extends Model {
    use HasFactory;
    use HasAsset;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'quote_request_assets';

    /**
     * @return BelongsTo<QuoteRequestDuration,self>
     */
    public function duration(): BelongsTo {
        return $this->belongsTo(QuoteRequestDuration::class, 'duration_id');
    }

    /**
     * @return BelongsTo<ServiceLevel, self>
     */
    public function serviceLevel(): BelongsTo {
        return $this->belongsTo(ServiceLevel::class);
    }

    /**
     * @return BelongsTo<QuoteRequest, self>
     */
    public function request(): BelongsTo {
        return $this->belongsTo(QuoteRequest::class, 'request_id');
    }
}
