<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Asset Coverage.
 *
 * @property string               $id
 * @property string               $asset_id
 * @property string               $coverage_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static Builder|AssetCoverage newModelQuery()
 * @method static Builder|AssetCoverage newQuery()
 * @method static Builder|AssetCoverage query()
 */
class AssetCoverage extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_coverages';
}
