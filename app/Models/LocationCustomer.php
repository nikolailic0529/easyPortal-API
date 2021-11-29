<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use App\Utils\Eloquent\SmartSave\Upsertable;

/**
 * LocationCustomer.
 *
 * @property string                       $id
 * @property string                       $location_id
 * @property string                       $customer_id
 * @property int                          $assets_count
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationCustomer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationCustomer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\LocationCustomer query()
 * @mixin \Eloquent
 */
class LocationCustomer extends Pivot implements Upsertable {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'location_customers';

    /**
     * @inheritDoc
     */
    public static function getUniqueKey(): array {
        return ['location_id', 'customer_id'];
    }
}
