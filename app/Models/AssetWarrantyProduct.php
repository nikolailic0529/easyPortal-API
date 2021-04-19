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
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyProduct whereAssetWarrantyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyProduct whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetWarrantyProduct whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AssetWarrantyProduct extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'asset_warranty_products';
}
