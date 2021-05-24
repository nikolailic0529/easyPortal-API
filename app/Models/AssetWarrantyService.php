<?php declare(strict_types = 1);

namespace App\Models;

/**
 * Asset Warranty Product (pivot)
 *
 * @property string                       $id
 * @property string                       $asset_warranty_id
 * @property string                       $product_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyService query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyService whereAssetWarrantyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyService whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyService whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyService whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AssetWarrantyService extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_warranty_services';
}
