<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;

/**
 * Asset Coverage.
 *
 * @property string                       $id
 * @property string                       $asset_id
 * @property string                       $coverage_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetCoverage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetCoverage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetCoverage query()
 * @mixin \Eloquent
 */
class AssetCoverage extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_coverages';
}
