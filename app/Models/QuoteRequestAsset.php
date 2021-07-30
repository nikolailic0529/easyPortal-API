<?php declare(strict_types = 1);

namespace App\Models;

/**
 * App\Models\QuoteRequestAsset
 *
 * @property string                       $id
 * @property string                       $request_id
 * @property string                       $asset_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
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
}
