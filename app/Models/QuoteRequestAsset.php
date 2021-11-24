<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasAsset;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * QuoteRequestAsset.
 *
 * @property string                                $id
 * @property string                                $request_id
 * @property string                                $asset_id
 * @property string                                $duration_id
 * @property string                                $service_level_id
 * @property \Carbon\CarbonImmutable               $created_at
 * @property \Carbon\CarbonImmutable               $updated_at
 * @property \Carbon\CarbonImmutable|null          $deleted_at
 * @property \App\Models\Asset                     $asset
 * @property-read \App\Models\QuoteRequestDuration $duration
 * @property-read \App\Models\ServiceLevel         $serviceLevel
 * @property-read \App\Models\QuoteRequest         $request
 * @method static \Database\Factories\QuoteRequestAssetFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequestAsset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequestAsset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequestAsset query()
 * @mixin \Eloquent
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

    public function duration(): BelongsTo {
        return $this->belongsTo(QuoteRequestDuration::class, 'duration_id');
    }

    public function serviceLevel(): BelongsTo {
        return $this->belongsTo(ServiceLevel::class);
    }

    public function request(): BelongsTo {
        return $this->belongsTo(QuoteRequest::class, 'request_id');
    }
}
