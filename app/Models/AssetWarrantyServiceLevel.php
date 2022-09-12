<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use Carbon\CarbonImmutable;
use Database\Factories\AssetWarrantyServiceLevelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Asset Warranty Product (pivot)
 *
 * @property string               $id
 * @property string               $asset_warranty_id
 * @property string               $service_level_id
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static AssetWarrantyServiceLevelFactory factory(...$parameters)
 * @method static Builder|AssetWarrantyServiceLevel newModelQuery()
 * @method static Builder|AssetWarrantyServiceLevel newQuery()
 * @method static Builder|AssetWarrantyServiceLevel query()
 */
class AssetWarrantyServiceLevel extends Pivot {
    use HasFactory;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_warranty_service_levels';
}
