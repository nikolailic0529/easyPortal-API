<?php declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\QuoteRequestAsset
 *
 * @property string                       $id
 * @property string                       $request_id
 * @property string                       $asset_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Duration         $duration
 * @property \App\Models\ServiceLevel     $serviceLevel
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequestAsset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequestAsset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\QuoteRequestAsset query()
 * @mixin \Eloquent
 */
class QuoteRequestAsset extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'quote_request_assets';

    public function duration(): BelongsTo {
        return $this->belongsTo(Duration::class);
    }

    public function serviceLevel(): BelongsTo {
        return $this->belongsTo(ServiceLevel::class);
    }
}
