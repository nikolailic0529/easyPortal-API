<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use App\Utils\Eloquent\SmartSave\Upsertable;
use Carbon\CarbonImmutable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;

/**
 * LocationCustomer.
 *
 * @property string               $id
 * @property string               $location_id
 * @property string               $customer_id
 * @property int                  $assets_count
 * @property CarbonImmutable      $created_at
 * @property CarbonImmutable      $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @method static Builder|LocationCustomer newModelQuery()
 * @method static Builder|LocationCustomer newQuery()
 * @method static Builder|LocationCustomer query()
 * @mixin Eloquent
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
