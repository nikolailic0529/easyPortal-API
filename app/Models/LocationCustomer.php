<?php declare(strict_types = 1);

namespace App\Models;

use App\Utils\Eloquent\Pivot;
use App\Utils\Eloquent\SmartSave\Upsertable;
use Carbon\CarbonImmutable;
use Database\Factories\LocationCustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
 * @method static LocationCustomerFactory factory(...$parameters)
 * @method static Builder|LocationCustomer newModelQuery()
 * @method static Builder|LocationCustomer newQuery()
 * @method static Builder|LocationCustomer query()
 */
class LocationCustomer extends Pivot implements Upsertable {
    use HasFactory;

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
