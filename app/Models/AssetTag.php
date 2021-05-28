<?php declare(strict_types = 1);

namespace App\Models;

/**
 * Asset Tag (pivot)
 *
 * @property string                       $id
 * @property string                       $asset_id
 * @property string                       $tag_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTag query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTag whereAssetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTag whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTag whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AssetTag whereUpdatedAt($value)
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
