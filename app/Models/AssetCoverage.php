<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\AssetCoverageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Asset Coverage.
 *
 * @property string               $id
 * @property string               $asset_id
 * @property string               $coverage_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static AssetCoverageFactory factory(...$parameters)
 * @method static Builder<AssetCoverage>|AssetCoverage newModelQuery()
 * @method static Builder<AssetCoverage>|AssetCoverage newQuery()
 * @method static Builder<AssetCoverage>|AssetCoverage query()
 */
class AssetCoverage extends Pivot {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_coverages';
}
