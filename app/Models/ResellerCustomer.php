<?php declare(strict_types = 1);

namespace App\Models;

use App\Models\Relations\HasKpi;
use App\Utils\Eloquent\Pivot;
use App\Utils\Eloquent\SmartSave\Upsertable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ResellerCustomer.
 *
 * @property string                       $id
 * @property string                       $reseller_id
 * @property string                       $customer_id
 * @property string|null                  $kpi_id
 * @property int                          $assets_count
 * @property int                          $locations_count
 * @property \Carbon\CarbonImmutable      $created_at
 * @property \Carbon\CarbonImmutable      $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \App\Models\Kpi|null         $kpi
 * @method static \Database\Factories\ResellerCustomerFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ResellerCustomer query()
 * @mixin \Eloquent
 */
class ResellerCustomer extends Pivot implements Upsertable {
    use HasFactory;
    use HasKpi;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $table = 'reseller_customers';

    /**
     * @inheritDoc
     */
    public static function getUniqueKey(): array {
        return ['reseller_id', 'customer_id'];
    }
}
