<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;

/**
 * LocationReseller.
 *
 * @property string                       $id
 * @property string                       $location_id
 * @property string                       $reseller_id
 * @property int                          $assets_count
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationReseller newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationReseller newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationReseller query()
 * @mixin \Eloquent
 */
class LocationReseller extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'location_resellers';
}
