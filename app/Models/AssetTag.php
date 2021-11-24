<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;

/**
 * Asset Tag (pivot)
 *
 * @property string                       $id
 * @property string                       $tag_id
 * @property string                       $asset_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTag query()
 * @mixin \Eloquent
 */
class AssetTag extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_tags';
}
