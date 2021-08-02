<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Concerns\HasAsset;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\QuoteRequestAsset
 *
 * @property string                       $id
 * @property string                       $request_id
 * @property string                       $asset_id
 * @property string                       $duration_id
 * @property string                       $service_level_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Asset            $asset
 * @property \App\Models\Duration         $duration
 * @property \App\Models\ServiceLevel     $serviceLevel
 * @property \App\Models\QuoteRequest     $request
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
