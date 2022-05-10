<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Asset Tag (pivot)
 *
 * @property string               $id
 * @property string               $tag_id
 * @property string               $asset_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static Builder|AssetTag newModelQuery()
 * @method static Builder|AssetTag newQuery()
 * @method static Builder|AssetTag query()
 */
class AssetTag extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_tags';
}
