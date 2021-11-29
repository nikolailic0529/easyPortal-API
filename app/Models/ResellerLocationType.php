<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;

/**
 * ResellerLocationType.
 *
 * @property string                       $id
 * @property string                       $reseller_location_id
 * @property string                       $type_id
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerLocationType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerLocationType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerLocationType query()
 * @mixin \Eloquent
 */
class ResellerLocationType extends Pivot {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'reseller_location_types';
}
