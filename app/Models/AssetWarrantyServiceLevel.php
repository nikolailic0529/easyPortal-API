<?php declare(strict_types = 1);

namespace App\Models;

/**
 * Asset Warranty Product (pivot)
 *
 * @property string                       $id
 * @property string                       $asset_warranty_id
 * @property string                       $service_level_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyServiceLevel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyServiceLevel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyServiceLevel query()
 * @mixin \Eloquent
 */
class AssetWarrantyServiceLevel extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_warranty_service_levels';
}
