<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAsset;
use App\Utils\Eloquent\CascadeDeletes\CascadeDelete;
use App\Utils\Eloquent\Model;
use Carbon\CarbonImmutable;
use Database\Factories\QuoteRequestAssetFactory;
use Eloquent;
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
 * @property string                    $service_level_id
 * @property CarbonImmutable           $created_at
 * @property CarbonImmutable           $updated_at
 * @property CarbonImmutable|null      $deleted_at
 * @property Asset                     $asset
 * @property-read QuoteRequestDuration $duration
 * @property-read ServiceLevel         $serviceLevel
 * @property-read QuoteRequest         $request
 * @method static QuoteRequestAssetFactory factory(...$parameters)
 * @method static Builder|QuoteRequestAsset newModelQuery()
 * @method static Builder|QuoteRequestAsset newQuery()
 * @method static Builder|QuoteRequestAsset query()
 * @mixin Eloquent
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

    #[CascadeDelete(false)]
    public function duration(): BelongsTo {
        return $this->belongsTo(QuoteRequestDuration::class, 'duration_id');
    }

    #[CascadeDelete(false)]
    public function serviceLevel(): BelongsTo {
        return $this->belongsTo(ServiceLevel::class);
    }

    #[CascadeDelete(false)]
    public function request(): BelongsTo {
        return $this->belongsTo(QuoteRequest::class, 'request_id');
    }
}
